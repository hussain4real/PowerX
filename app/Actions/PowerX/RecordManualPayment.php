<?php

namespace App\Actions\PowerX;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Http\UploadedFile;

class RecordManualPayment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $invoice, array $data, ?UploadedFile $proof = null): PaymentTransaction
    {
        $payment = PaymentTransaction::create([
            'team_id' => $invoice->team_id,
            'enrollment_id' => $invoice->enrollment_id,
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'student_profile_id' => $invoice->student_profile_id,
            'method' => $data['method'] ?? 'bank_transfer',
            'provider' => $data['provider'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => 'pending',
            'currency' => $data['currency'] ?? $invoice->currency,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? now(),
            'metadata' => [
                'received_by' => $data['received_by'] ?? 'manual_entry',
                'notes' => $data['notes'] ?? null,
            ],
        ]);

        if ($proof instanceof UploadedFile) {
            $payment->addMedia($proof)->toMediaCollection('payment-proofs');
        }

        return $payment;
    }
}
