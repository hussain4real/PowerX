<?php

namespace App\Actions\PowerX;

use App\Communications\CommunicationDeliveryResult;
use App\Communications\HttpCommunicationProvider;
use App\Communications\NullCommunicationProvider;
use App\Contracts\Communications\CommunicationProvider;
use App\Models\Communication;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class DispatchReadyProviderCommunications
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @return array{accepted: int, fallback: int, failed: int, opted_out: int}
     */
    public function handle(?CarbonInterface $asOf = null, ?int $limit = null): array
    {
        $asOf ??= now();
        $limit ??= max(
            (int) config('powerx_notifications.delivery.whatsapp.dispatch_limit', 100),
            (int) config('powerx_notifications.delivery.sms.dispatch_limit', 100),
        );

        $counts = [
            'accepted' => 0,
            'fallback' => 0,
            'failed' => 0,
            'opted_out' => 0,
        ];

        $communications = Communication::query()
            ->readyForDelivery($asOf)
            ->whereIn('channel', [Communication::CHANNEL_WHATSAPP, Communication::CHANNEL_SMS])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($communications as $communication) {
            if ($this->recipientPreviouslyOptedOut($communication)) {
                $communication->markOptedOut($asOf, 'Recipient previously opted out of '.$communication->channel.' communications.');
                $counts['opted_out']++;

                continue;
            }

            $communication->markQueued($asOf);
            $result = $this->providerFor($communication->channel)->deliver($communication->refresh());
            $communication->mergeMetadata($result->toMetadata());
            $this->applyResult($communication->refresh(), $result, $asOf);
            $counts[$result->status]++;
        }

        return $counts;
    }

    private function providerFor(string $channel): CommunicationProvider
    {
        $enabled = (bool) config("powerx_notifications.delivery.{$channel}.enabled", false);
        $driver = (string) config("powerx_notifications.delivery.{$channel}.driver", 'null');
        $endpoint = (string) config("powerx_notifications.delivery.{$channel}.endpoint", '');

        if (! $enabled || $driver !== 'http' || blank($endpoint)) {
            return new NullCommunicationProvider(
                channel: $channel,
                logger: $this->logger,
                pendingSignOffItems: array_values((array) config('powerx_notifications.provider.pending_sign_off', [])),
            );
        }

        $token = config("powerx_notifications.delivery.{$channel}.token");

        return new HttpCommunicationProvider(
            provider: $channel,
            endpoint: $endpoint,
            token: is_string($token) ? $token : null,
            timeout: (int) config("powerx_notifications.delivery.{$channel}.timeout", 10),
            logger: $this->logger,
        );
    }

    private function applyResult(Communication $communication, CommunicationDeliveryResult $result, CarbonInterface $asOf): void
    {
        if ($result->status === CommunicationDeliveryResult::STATUS_ACCEPTED) {
            $communication->markAccepted($asOf);

            return;
        }

        if ($result->status === CommunicationDeliveryResult::STATUS_FALLBACK) {
            $communication->markFallbackAvailable($asOf, $result->failureReason);

            return;
        }

        if ($result->retryAt !== null && ((int) $communication->retry_count) < (int) config('powerx_notifications.provider.max_retries', 3)) {
            $communication->scheduleRetry($result->retryAt, $result->failureReason);

            return;
        }

        if ((int) $communication->retry_count >= (int) config('powerx_notifications.provider.max_retries', 3)) {
            $communication->markDeadLetter($result->failureReason ?? 'Provider delivery failed.', $asOf);

            return;
        }

        $communication->markFailed($result->failureReason ?? 'Provider delivery failed.', $asOf);
    }

    private function recipientPreviouslyOptedOut(Communication $communication): bool
    {
        $recipientKey = $this->recipientKey($communication);

        if ($recipientKey === null) {
            return false;
        }

        return $this->optedOutCommunications($communication)
            ->contains(fn (Communication $optedOut): bool => $this->recipientKey($optedOut) === $recipientKey);
    }

    /**
     * @return Collection<int, Communication>
     */
    private function optedOutCommunications(Communication $communication): Collection
    {
        return Communication::query()
            ->where('id', '!=', $communication->id)
            ->where('channel', $communication->channel)
            ->whereNotNull('opted_out_at')
            ->when(
                $communication->team_id !== null,
                fn ($query) => $query->where('team_id', $communication->team_id),
            )
            ->get();
    }

    private function recipientKey(Communication $communication): ?string
    {
        $phone = data_get($communication->metadata, 'recipient_phone_normalized')
            ?? data_get($communication->metadata, 'recipient_phone')
            ?? data_get($communication->metadata, 'template_context.recipient_phone')
            ?? $communication->studentProfile?->mobile
            ?? $communication->lead?->phone
            ?? $communication->company?->phone;

        $digits = str((string) $phone)->replaceMatches('/\D+/', '')->toString();

        return $digits === '' ? null : $digits;
    }
}
