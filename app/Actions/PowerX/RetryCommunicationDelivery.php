<?php

namespace App\Actions\PowerX;

use App\Models\Communication;
use Carbon\CarbonInterface;

class RetryCommunicationDelivery
{
    public function handle(Communication $communication, ?CarbonInterface $retryAt = null, ?string $reason = null): bool
    {
        if ($communication->opted_out_at !== null || $communication->status === Communication::STATUS_OPTED_OUT) {
            return false;
        }

        if ($communication->status === Communication::STATUS_DEAD_LETTER) {
            $communication->forceFill([
                'retry_count' => 0,
                'failed_at' => null,
            ])->save();
        }

        return $communication->scheduleRetry(
            $retryAt ?? now(),
            $reason ?? 'Manually queued for provider retry.',
        );
    }
}
