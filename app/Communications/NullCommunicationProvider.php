<?php

namespace App\Communications;

use App\Contracts\Communications\CommunicationProvider;
use App\Models\Communication;
use Psr\Log\LoggerInterface;

class NullCommunicationProvider implements CommunicationProvider
{
    /**
     * @param  list<string>  $pendingSignOffItems
     */
    public function __construct(
        private readonly string $channel,
        private readonly LoggerInterface $logger,
        private array $pendingSignOffItems = [],
    ) {
        $this->pendingSignOffItems = $pendingSignOffItems ?: config('powerx_notifications.provider.pending_sign_off', []);
    }

    public function deliver(Communication $communication): CommunicationDeliveryResult
    {
        $metadata = [
            'pending_sign_off' => $this->pendingSignOffItems,
        ];

        if ($communication->channel === Communication::CHANNEL_WHATSAPP && filled(data_get($communication->metadata, 'whatsapp_url'))) {
            $metadata['fallback_url'] = data_get($communication->metadata, 'whatsapp_url');

            $this->logger->info('WhatsApp delivery deferred to click-to-chat fallback.', [
                'communication_id' => $communication->id,
                'fallback_url' => $metadata['fallback_url'],
            ]);

            return CommunicationDeliveryResult::fallback(
                provider: 'null',
                failureReason: 'WhatsApp provider is disabled until PowerX approves a Business API provider.',
                metadata: $metadata,
            );
        }

        $this->logger->info('Communication provider delivery deferred until channel sign-off.', [
            'communication_id' => $communication->id,
            'channel' => $this->channel,
        ]);

        return CommunicationDeliveryResult::failed(
            provider: 'null',
            failureReason: str($this->channel)->upper()->append(' provider is disabled until PowerX approves the channel.')->toString(),
            metadata: $metadata,
        );
    }
}
