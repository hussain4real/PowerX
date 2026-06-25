<?php

namespace App\Communications;

use App\Contracts\Communications\CommunicationProvider;
use App\Models\Communication;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

class HttpCommunicationProvider implements CommunicationProvider
{
    public function __construct(
        private readonly string $provider,
        private readonly string $endpoint,
        private readonly ?string $token,
        private readonly int $timeout,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(Communication $communication): CommunicationDeliveryResult
    {
        try {
            $response = Http::when(
                filled($this->token),
                fn ($http) => $http->withToken((string) $this->token),
            )
                ->acceptJson()
                ->timeout($this->timeout)
                ->post($this->endpoint, $this->payload($communication));
        } catch (ConnectionException $exception) {
            return CommunicationDeliveryResult::failed(
                provider: $this->provider,
                failureReason: $exception->getMessage(),
                retryAt: now()->addMinutes((int) config('powerx_notifications.provider.retry_delay_minutes', 15)),
            );
        }

        if ($response->successful()) {
            return CommunicationDeliveryResult::accepted(
                provider: $this->provider,
                providerMessageId: (string) ($response->json('message_id') ?? $response->json('id') ?? $communication->id),
                providerStatus: (string) ($response->json('status') ?? 'accepted'),
                metadata: [
                    'response' => $response->json(),
                ],
            );
        }

        $retryAt = $response->serverError() || $response->status() === 429
            ? now()->addMinutes((int) config('powerx_notifications.provider.retry_delay_minutes', 15))
            : null;

        $this->logger->warning('Communication provider rejected delivery.', [
            'communication_id' => $communication->id,
            'provider' => $this->provider,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return CommunicationDeliveryResult::failed(
            provider: $this->provider,
            failureReason: str($response->json('message') ?? $response->body() ?: 'Provider rejected delivery.')->limit(255, '')->toString(),
            retryAt: $retryAt,
            metadata: [
                'status_code' => $response->status(),
                'response' => $response->json(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Communication $communication): array
    {
        return [
            'id' => $communication->id,
            'channel' => $communication->channel,
            'template_key' => $communication->template_key,
            'subject' => $communication->subject,
            'message' => $communication->message,
            'recipient' => [
                'name' => data_get($communication->metadata, 'recipient_name'),
                'email' => data_get($communication->metadata, 'recipient_email'),
                'phone' => data_get($communication->metadata, 'recipient_phone'),
                'phone_normalized' => data_get($communication->metadata, 'recipient_phone_normalized'),
            ],
            'metadata' => [
                'team_id' => $communication->team_id,
                'lead_id' => $communication->lead_id,
                'student_profile_id' => $communication->student_profile_id,
                'company_id' => $communication->company_id,
                'user_id' => $communication->user_id,
            ],
        ];
    }
}
