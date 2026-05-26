<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ApproveManualPayment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * Approve a manual payment and synchronize invoice/enrollment state.
     */
    public function handle(PaymentTransaction $paymentTransaction, User $approver): PaymentTransaction
    {
        return DB::transaction(function () use ($paymentTransaction, $approver) {
            $payment = PaymentTransaction::query()
                ->whereKey($paymentTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = null;
            $wasApproved = false;

            if ($payment->status !== PaymentTransaction::STATUS_APPROVED) {
                $before = $this->paymentAuditSnapshot($payment);
                $wasApproved = true;

                $payment->update([
                    'status' => PaymentTransaction::STATUS_APPROVED,
                    'approved_by_id' => $approver->id,
                    'approved_at' => now(),
                    'paid_at' => $payment->paid_at ?? now(),
                ]);
            }

            if ($payment->invoice_id) {
                $invoice = Invoice::query()
                    ->whereKey($payment->invoice_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->syncInvoiceStatus($invoice);
            }

            if ($payment->enrollment_id) {
                $enrollment = Enrollment::query()
                    ->whereKey($payment->enrollment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->syncEnrollmentPaymentStatus($enrollment);
            }

            if ($wasApproved) {
                $payment->refresh();

                $this->recordAuditEvent->handle(
                    action: 'payment.approved',
                    subject: $payment,
                    actor: $approver,
                    before: $before,
                    after: $this->paymentAuditSnapshot($payment),
                    metadata: [
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'invoice_id' => $payment->invoice_id,
                        'enrollment_id' => $payment->enrollment_id,
                    ],
                    summary: __('Manual payment approved.'),
                );
            }

            return $payment->refresh();
        });
    }

    private function syncInvoiceStatus(Invoice $invoice): void
    {
        $approvedTotal = (float) $invoice->paymentTransactions()->approved()->sum('amount');
        $invoiceTotal = (float) $invoice->total;

        $invoice->update([
            'status' => match (true) {
                $approvedTotal <= 0 => 'issued',
                $approvedTotal < $invoiceTotal => 'partial',
                default => 'paid',
            },
            'paid_at' => $approvedTotal >= $invoiceTotal ? now() : null,
        ]);
    }

    private function syncEnrollmentPaymentStatus(Enrollment $enrollment): void
    {
        $approvedTotal = (float) $enrollment->paymentTransactions()->approved()->sum('amount');
        $invoiceTotal = (float) $enrollment->invoices()->issued()->sum('total');
        $isPaid = $invoiceTotal > 0 && $approvedTotal >= $invoiceTotal;

        $enrollment->update([
            'payment_status' => match (true) {
                $approvedTotal <= 0 => 'pending',
                ! $isPaid => 'partial',
                default => 'paid',
            },
            'status' => $isPaid && $enrollment->status === 'pending' ? 'active' : $enrollment->status,
            'access_starts_at' => $isPaid ? ($enrollment->access_starts_at ?? now()) : $enrollment->access_starts_at,
            'access_expires_at' => $isPaid
                ? ($enrollment->access_expires_at ?? $this->accessExpiry($enrollment))
                : $enrollment->access_expires_at,
        ]);
    }

    private function accessExpiry(Enrollment $enrollment): ?CarbonInterface
    {
        $validityDays = $enrollment->coursePackage?->validity_days ?? $enrollment->course->validity_days;

        return $validityDays ? now()->addDays($validityDays) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAuditSnapshot(PaymentTransaction $payment): array
    {
        return [
            'status' => $payment->status,
            'approved_by_id' => $payment->approved_by_id,
            'approved_at' => $payment->approved_at?->toISOString(),
            'paid_at' => $payment->paid_at?->toISOString(),
        ];
    }
}
