<?php

namespace App\Actions\PowerX;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveManualPayment
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private SyncOfflinePaymentLedger $syncOfflinePaymentLedger,
    ) {}

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

            $this->syncOfflinePaymentLedger->handle($payment);

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
