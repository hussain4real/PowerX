<?php

namespace App\Console\Commands;

use App\Actions\PowerX\DispatchReadyEmailCommunications;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('powerx:communications:dispatch {--limit= : Maximum ready email communications to queue}')]
#[Description('Queue ready PowerX email communications for delivery')]
class DispatchPowerXCommunications extends Command
{
    public function handle(DispatchReadyEmailCommunications $dispatchReadyEmailCommunications): int
    {
        $limit = $this->option('limit');
        $queued = $dispatchReadyEmailCommunications->handle(limit: $limit === null ? null : (int) $limit);

        $this->components->info("Queued {$queued} PowerX email communication(s).");

        return self::SUCCESS;
    }
}
