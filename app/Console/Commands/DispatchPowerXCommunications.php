<?php

namespace App\Console\Commands;

use App\Actions\PowerX\DispatchReadyEmailCommunications;
use App\Actions\PowerX\DispatchReadyProviderCommunications;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('powerx:communications:dispatch {--limit= : Maximum ready communications to dispatch per channel pass}')]
#[Description('Queue ready PowerX email communications and dispatch ready provider communications')]
class DispatchPowerXCommunications extends Command
{
    public function handle(
        DispatchReadyEmailCommunications $dispatchReadyEmailCommunications,
        DispatchReadyProviderCommunications $dispatchReadyProviderCommunications,
    ): int {
        $limit = $this->option('limit');
        $resolvedLimit = $limit === null ? null : (int) $limit;
        $queued = $dispatchReadyEmailCommunications->handle(limit: $resolvedLimit);
        $providerCounts = $dispatchReadyProviderCommunications->handle(limit: $resolvedLimit);

        $this->components->info("Queued {$queued} PowerX email communication(s).");
        $this->components->info(sprintf(
            'Processed %d provider communication(s).',
            array_sum($providerCounts),
        ));

        return self::SUCCESS;
    }
}
