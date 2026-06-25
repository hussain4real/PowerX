<?php

namespace App\Communications;

use Carbon\CarbonInterface;

class CommunicationDeliveryResult
{
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_FALLBACK = 'fallback';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $status,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $failureReason = null,
        public readonly ?CarbonInterface $retryAt = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function accepted(string $provider, ?string $providerMessageId = null, ?string $providerStatus = null, array $metadata = []): self
    {
        return new self(
            status: self::STATUS_ACCEPTED,
            provider: $provider,
            providerMessageId: $providerMessageId,
            providerStatus: $providerStatus,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function fallback(string $provider, string $failureReason, array $metadata = []): self
    {
        return new self(
            status: self::STATUS_FALLBACK,
            provider: $provider,
            failureReason: $failureReason,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function failed(string $provider, string $failureReason, ?CarbonInterface $retryAt = null, array $metadata = []): self
    {
        return new self(
            status: self::STATUS_FAILED,
            provider: $provider,
            failureReason: $failureReason,
            retryAt: $retryAt,
            metadata: $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadata(): array
    {
        return [
            'provider' => [
                'name' => $this->provider,
                'status' => $this->status,
                'message_id' => $this->providerMessageId,
                'provider_status' => $this->providerStatus,
                'failure_reason' => $this->failureReason,
                'retry_at' => $this->retryAt?->toISOString(),
                'metadata' => $this->metadata,
            ],
        ];
    }
}
