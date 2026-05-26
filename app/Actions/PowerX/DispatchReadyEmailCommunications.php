<?php

namespace App\Actions\PowerX;

use App\Jobs\DeliverEmailCommunication;
use App\Models\Communication;
use Carbon\CarbonInterface;

class DispatchReadyEmailCommunications
{
    public function handle(?CarbonInterface $asOf = null, ?int $limit = null): int
    {
        if (! config('powerx_notifications.delivery.email.enabled', true)) {
            return 0;
        }

        $asOf ??= now();
        $limit ??= (int) config('powerx_notifications.delivery.email.dispatch_limit', 100);
        $queue = (string) config('powerx_notifications.delivery.email.queue', 'mail');
        $queued = 0;

        $communications = Communication::query()
            ->readyForDelivery($asOf)
            ->where('channel', Communication::CHANNEL_EMAIL)
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($communications as $communication) {
            $communication->markQueued($asOf);

            DeliverEmailCommunication::dispatch($communication->id)
                ->onQueue($queue)
                ->afterCommit();

            $queued++;
        }

        return $queued;
    }
}
