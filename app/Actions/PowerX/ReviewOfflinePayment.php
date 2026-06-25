<?php

namespace App\Actions\PowerX;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewOfflinePayment
{
    /**
     * @var array<string, string>
     */
    private const OUTCOME_STATUSES = [
        'reject' => PaymentTransaction::STATUS_REJECTED,
        'request_information' => PaymentTransaction::STATUS_INFORMATION_REQUESTED,
        'duplicate' => PaymentTransaction::STATUS_DUPLICATE,
        'partial' => PaymentTransaction::STATUS_PARTIAL,
        'void' => PaymentTransaction::STATUS_VOIDED,
        'refund' => PaymentTransaction::STATUS_REFUNDED,
        'adjust' => PaymentTransaction::STATUS_ADJUSTED,
    ];

    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private SyncOfflinePaymentLedger $syncOfflinePaymentLedger,
    ) {}

    /**
     * @param  array{notes?: string|null, amount?: numeric-string|int|float|null}  $data
     */
    public function handle(PaymentTransaction $paymentTransaction, User $reviewer, string $outcome, array $data = []): PaymentTransaction
    {
        $status = self::OUTCOME_STATUSES[$outcome] ?? null;

        if ($status === null) {
            throw new InvalidArgumentException("Unsupported offline payment review outcome [{$outcome}].");
        }

        return DB::transaction(function () use ($paymentTransaction, $reviewer, $outcome, $status, $data): PaymentTransaction {
            $payment = PaymentTransaction::query()
                ->whereKey($paymentTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->paymentAuditSnapshot($payment);
            $metadata = $payment->metadata ?? [];
            $review = [
                'outcome' => $outcome,
                'status' => $status,
                'reviewer_id' => $reviewer->id,
                'reviewer_name' => $reviewer->name,
                'reviewed_at' => now()->toISOString(),
                'notes' => $data['notes'] ?? null,
            ];

            if ($outcome === 'adjust' && isset($data['amount'])) {
                $review['previous_amount'] = (float) $payment->amount;
                $review['adjusted_amount'] = (float) $data['amount'];
                $payment->amount = $data['amount'];
            }

            $metadata['finance_review'] = $review;
            $metadata['finance_review_history'] = [
                ...($metadata['finance_review_history'] ?? []),
                $review,
            ];

            $payment->forceFill([
                'status' => $status,
                'approved_by_id' => $status === PaymentTransaction::STATUS_APPROVED ? $reviewer->id : $payment->approved_by_id,
                'approved_at' => $status === PaymentTransaction::STATUS_APPROVED ? now() : $payment->approved_at,
                'metadata' => $metadata,
            ])->save();

            $this->syncOfflinePaymentLedger->handle($payment);

            $payment->refresh();

            $this->recordAuditEvent->handle(
                action: "payment.{$outcome}",
                subject: $payment,
                actor: $reviewer,
                before: $before,
                after: $this->paymentAuditSnapshot($payment),
                metadata: [
                    'invoice_id' => $payment->invoice_id,
                    'enrollment_id' => $payment->enrollment_id,
                    'outcome' => $outcome,
                    'notes' => $data['notes'] ?? null,
                ],
                summary: __('Offline payment review recorded.'),
            );

            return $payment;
        });
    }

    /**
     * @return array<string, string>
     */
    public static function outcomeOptions(): array
    {
        return [
            'reject' => 'Reject',
            'request_information' => 'Request more information',
            'duplicate' => 'Mark duplicate',
            'partial' => 'Mark partial',
            'void' => 'Void',
            'refund' => 'Refund',
            'adjust' => 'Adjust',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAuditSnapshot(PaymentTransaction $payment): array
    {
        return [
            'status' => $payment->status,
            'amount' => (float) $payment->amount,
            'approved_by_id' => $payment->approved_by_id,
            'approved_at' => $payment->approved_at?->toISOString(),
            'finance_review' => data_get($payment->metadata, 'finance_review'),
        ];
    }
}
