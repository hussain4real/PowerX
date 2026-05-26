<?php

namespace App\Payments;

use App\Enums\PaymentReconciliationStatus;

readonly class PaymentReconciliationResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public PaymentReconciliationStatus $status,
        public string $provider,
        public int $paymentTransactionId,
        public ?string $providerReference,
        public ?string $providerStatus,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'status' => $this->status->value,
            'provider' => $this->provider,
            'payment_transaction_id' => $this->paymentTransactionId,
            'provider_reference' => $this->providerReference,
            'provider_status' => $this->providerStatus,
            'metadata' => PaymentGatewayMetadata::sanitize($this->metadata),
        ];
    }
}
