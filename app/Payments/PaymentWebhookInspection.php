<?php

namespace App\Payments;

use App\Enums\PaymentWebhookStatus;

readonly class PaymentWebhookInspection
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public PaymentWebhookStatus $status,
        public string $provider,
        public ?string $eventId,
        public ?string $transactionReference,
        public ?bool $signatureVerified,
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
            'event_id' => $this->eventId,
            'transaction_reference' => $this->transactionReference,
            'signature_verified' => $this->signatureVerified,
            'metadata' => PaymentGatewayMetadata::sanitize($this->metadata),
        ];
    }
}
