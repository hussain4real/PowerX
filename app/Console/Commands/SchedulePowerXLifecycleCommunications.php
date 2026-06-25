<?php

namespace App\Console\Commands;

use App\Actions\PowerX\ScheduleLifecycleCommunications;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('powerx:communications:schedule-lifecycle')]
#[Description('Schedule due PowerX lifecycle reminder communications')]
class SchedulePowerXLifecycleCommunications extends Command
{
    public function handle(ScheduleLifecycleCommunications $scheduleLifecycleCommunications): int
    {
        $counts = $scheduleLifecycleCommunications->handle();

        $this->components->info(sprintf(
            'Scheduled %d lifecycle communication(s).',
            array_sum($counts),
        ));

        foreach ($counts as $type => $count) {
            $this->line(str($type)->replace('_', ' ')->title().": {$count}");
        }

        return self::SUCCESS;
    }
}
