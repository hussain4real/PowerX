<?php

namespace App\Actions\PowerX;

use App\Models\Communication;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class ApplyCommunicationProviderWebhook
{
    public function __construct(private readonly OptOutCommunicationRecipient $optOutCommunicationRecipient) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): ?Communication
    {
        $communication = $this->findCommunication($payload);

        if (! $communication) {
            return null;
        }

        $occurredAt = $this->occurredAt($payload['occurred_at'] ?? null);
        $this->recordWebhookMetadata($communication, $payload, $occurredAt);

        match ((string) $payload['status']) {
            'accepted', 'sent' => $communication->markAccepted($occurredAt),
            'delivered' => $communication->markDelivered($occurredAt),
            'read' => $communication->markRead($occurredAt),
            'failed' => $this->markFailedOrRetry($communication, $payload, $occurredAt),
            'opt_out', 'opted_out', 'unsubscribed' => $this->optOutCommunicationRecipient->handle(
                $communication,
                $occurredAt,
                (string) ($payload['failure_reason'] ?? 'Provider reported recipient opt-out.'),
            ),
            default => null,
        };

        return $communication->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findCommunication(array $payload): ?Communication
    {
        if (filled($payload['communication_id'] ?? null)) {
            return Communication::query()->find((int) $payload['communication_id']);
        }

        if (blank($payload['provider_message_id'] ?? null)) {
            return null;
        }

        return Communication::query()
            ->where('metadata->provider->message_id', (string) $payload['provider_message_id'])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordWebhookMetadata(Communication $communication, array $payload, CarbonInterface $occurredAt): void
    {
        $provider = $communication->metadata['provider'] ?? [];
        $provider['last_webhook'] = [
            'provider' => $payload['provider'] ?? null,
            'channel' => $payload['channel'] ?? $communication->channel,
            'status' => $payload['status'],
            'provider_message_id' => $payload['provider_message_id'] ?? null,
            'event_id' => $payload['event_id'] ?? null,
            'occurred_at' => $occurredAt->toISOString(),
            'metadata' => Arr::get($payload, 'metadata', []),
        ];

        $communication->mergeMetadata([
            'provider' => $provider,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markFailedOrRetry(Communication $communication, array $payload, CarbonInterface $occurredAt): bool
    {
        $failureReason = (string) ($payload['failure_reason'] ?? 'Provider reported delivery failure.');
        $retryAt = isset($payload['retry_at']) ? $this->occurredAt($payload['retry_at']) : null;

        if ($retryAt !== null && ((int) $communication->retry_count) < (int) config('powerx_notifications.provider.max_retries', 3)) {
            return $communication->scheduleRetry($retryAt, $failureReason);
        }

        if ((int) $communication->retry_count >= (int) config('powerx_notifications.provider.max_retries', 3)) {
            return $communication->markDeadLetter($failureReason, $occurredAt);
        }

        return $communication->markFailed($failureReason, $occurredAt);
    }

    private function occurredAt(mixed $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return filled($value) ? CarbonImmutable::parse((string) $value) : now();
    }
}
