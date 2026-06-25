<?php

namespace App\Actions\PowerX;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class RecordManualPayment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $invoice, array $data, ?UploadedFile $proof = null): PaymentTransaction
    {
        $method = $data['method'] ?? config('powerx_payments.manual.default_method', PaymentTransaction::METHOD_BANK_TRANSFER);

        if (! array_key_exists($method, PaymentTransaction::manualMethodOptions())) {
            throw new InvalidArgumentException("Unsupported manual payment method [{$method}].");
        }

        $payment = PaymentTransaction::create([
            'team_id' => $invoice->team_id,
            'enrollment_id' => $invoice->enrollment_id,
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'student_profile_id' => $invoice->student_profile_id,
            'method' => $method,
            'provider' => $data['provider'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => PaymentTransaction::STATUS_PENDING,
            'currency' => $data['currency'] ?? $invoice->currency,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? now(),
            'metadata' => array_filter([
                'received_by' => $data['received_by'] ?? 'manual_entry',
                'notes' => $data['notes'] ?? null,
                'offline_payment' => Arr::whereNotNull([
                    'payer_name' => $data['payer_name'] ?? null,
                    'payer_email' => $data['payer_email'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'deposit_date' => $data['deposit_date'] ?? null,
                    'submitted_by_id' => $data['submitted_by_id'] ?? null,
                    'submitted_via' => $data['submitted_via'] ?? null,
                ]),
            ], fn (mixed $value): bool => $value !== []),
        ]);

        if ($proof instanceof UploadedFile) {
            $payment
                ->addMedia($proof)
                ->withCustomProperties(Arr::whereNotNull([
                    'source' => 'offline_payment_proof',
                    'uploaded_by_id' => $data['submitted_by_id'] ?? null,
                    'uploaded_via' => $data['submitted_via'] ?? null,
                    'uploaded_at' => now()->toISOString(),
                ]))
                ->toMediaCollection('payment-proofs');
        }

        return $payment;
    }
}
