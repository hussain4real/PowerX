<?php

namespace App\Payments;

use App\Enums\PaymentCheckoutStatus;

readonly class PaymentCheckoutPreparation
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public PaymentCheckoutStatus $status,
        public string $provider,
        public int $invoiceId,
        public string $amount,
        public string $currency,
        public ?string $checkoutUrl,
        public ?string $providerReference,
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
            'invoice_id' => $this->invoiceId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'has_checkout_url' => $this->checkoutUrl !== null,
            'provider_reference' => $this->providerReference,
            'metadata' => PaymentGatewayMetadata::sanitize($this->metadata),
        ];
    }
}
