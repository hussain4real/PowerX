<?php

namespace App\Payments;

readonly class PaymentWebhookPayload
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $provider,
        public ?string $eventId = null,
        public array $headers = [],
        public array $payload = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function sanitizedSummary(): array
    {
        return [
            'provider' => $this->provider,
            'event_id' => $this->eventId,
            'headers' => PaymentGatewayMetadata::sanitize($this->headers),
            'payload_keys' => array_map('strval', array_keys($this->payload)),
        ];
    }
}
