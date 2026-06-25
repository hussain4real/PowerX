<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SyncOfflinePaymentLedger
{
    public function handle(PaymentTransaction $paymentTransaction): void
    {
        DB::transaction(function () use ($paymentTransaction): void {
            if ($paymentTransaction->invoice_id) {
                $invoice = Invoice::query()
                    ->whereKey($paymentTransaction->invoice_id)
                    ->lockForUpdate()
                    ->first();

                if ($invoice) {
                    $this->syncInvoiceStatus($invoice);
                }
            }

            if ($paymentTransaction->enrollment_id) {
                $enrollment = Enrollment::query()
                    ->whereKey($paymentTransaction->enrollment_id)
                    ->lockForUpdate()
                    ->first();

                if ($enrollment) {
                    $this->syncEnrollmentPaymentStatus($enrollment);
                }
            }
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
            'status' => $isPaid && in_array($enrollment->status, [
                Enrollment::STATUS_PENDING,
                Enrollment::STATUS_APPROVED,
            ], true) ? Enrollment::STATUS_ACTIVE : $enrollment->status,
            'access_starts_at' => $isPaid ? ($enrollment->access_starts_at ?? now()) : null,
            'access_expires_at' => $isPaid
                ? ($enrollment->access_expires_at ?? $this->accessExpiry($enrollment))
                : null,
        ]);
    }

    private function accessExpiry(Enrollment $enrollment): ?CarbonInterface
    {
        $validityDays = $enrollment->coursePackage?->validity_days ?? $enrollment->course->validity_days;

        return $validityDays ? now()->addDays($validityDays) : null;
    }
}
