<?php

namespace App\Actions\PowerX;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecordLeadActivity
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        Lead $lead,
        User $actor,
        string $type,
        string $title,
        ?string $notes = null,
        ?string $nextStatus = null,
        ?string $outcome = null,
        ?CarbonInterface $followUpAt = null,
        ?string $channel = null,
        array $metadata = [],
    ): LeadActivity {
        return DB::transaction(function () use ($lead, $actor, $type, $title, $notes, $nextStatus, $outcome, $followUpAt, $channel, $metadata): LeadActivity {
            $lead = Lead::query()
                ->whereKey($lead->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->leadAuditSnapshot($lead);
            $previousStatus = $lead->status;
            $updates = [];

            if ($nextStatus !== null && $nextStatus !== $lead->status) {
                $updates['status'] = $nextStatus;
            }

            if ($outcome !== null) {
                $updates['outcome'] = $outcome;
            }

            if ($followUpAt !== null) {
                $updates['follow_up_at'] = $followUpAt;
            }

            if ($updates !== []) {
                $lead->update($updates);
            }

            $activity = LeadActivity::query()->create([
                'team_id' => $lead->team_id,
                'lead_id' => $lead->id,
                'actor_id' => $actor->id,
                'type' => $type,
                'title' => $title,
                'notes' => $notes,
                'channel' => $channel,
                'previous_status' => $previousStatus,
                'next_status' => $lead->fresh()->status,
                'follow_up_at' => $followUpAt,
                'metadata' => $metadata,
            ]);

            $lead->refresh();

            $this->recordAuditEvent->handle(
                action: 'lead.activity_recorded',
                subject: $lead,
                actor: $actor,
                before: $before,
                after: $this->leadAuditSnapshot($lead),
                metadata: [
                    'activity_id' => $activity->id,
                    'activity_type' => $type,
                    ...$metadata,
                ],
                summary: $title,
            );

            return $activity;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function leadAuditSnapshot(Lead $lead): array
    {
        return [
            'status' => $lead->status,
            'outcome' => $lead->outcome,
            'follow_up_at' => $lead->follow_up_at?->toISOString(),
            'converted_at' => $lead->converted_at?->toISOString(),
        ];
    }
}
