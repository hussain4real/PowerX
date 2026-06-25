<?php

namespace App\Actions\PowerX;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class SubmitOfflinePaymentProof
{
    public function __construct(
        private RecordManualPayment $recordManualPayment,
        private RecordAuditEvent $recordAuditEvent,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $invoice, User $submitter, array $data, ?UploadedFile $proof): PaymentTransaction
    {
        $payment = $this->recordManualPayment->handle($invoice, [
            ...$data,
            'received_by' => 'portal_upload',
            'submitted_by_id' => $submitter->id,
            'submitted_via' => $submitter->canViewCorporatePortal() ? 'corporate_portal' : 'student_portal',
        ]);

        if ($proof instanceof UploadedFile) {
            $payment
                ->addMedia($proof)
                ->withCustomProperties([
                    'source' => 'offline_payment_proof',
                    'uploaded_by_id' => $submitter->id,
                    'uploaded_via' => $submitter->canViewCorporatePortal() ? 'corporate_portal' : 'student_portal',
                    'uploaded_at' => now()->toISOString(),
                ])
                ->toMediaCollection('payment-proofs');
        }

        $this->recordAuditEvent->handle(
            action: 'payment.proof_submitted',
            subject: $payment,
            actor: $submitter,
            metadata: [
                'invoice_id' => $invoice->id,
                'enrollment_id' => $invoice->enrollment_id,
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
                'has_proof' => $proof instanceof UploadedFile,
            ],
            summary: __('Offline payment proof submitted.'),
        );

        return $payment;
    }
}
