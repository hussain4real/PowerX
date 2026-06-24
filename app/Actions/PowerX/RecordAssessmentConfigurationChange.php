<?php

namespace App\Actions\PowerX;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordAssessmentConfigurationChange
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     */
    public function handle(Model $subject, ?User $actor, array $before, array $after, array $metadata = []): void
    {
        $changes = collect($after)
            ->filter(fn (mixed $value, string $key): bool => ($before[$key] ?? null) !== $value)
            ->keys()
            ->values()
            ->all();

        if ($changes === []) {
            return;
        }

        $this->recordAuditEvent->handle(
            action: 'assessment.configuration_changed',
            subject: $subject,
            actor: $actor,
            before: $before,
            after: $after,
            metadata: [
                ...$metadata,
                'changed_fields' => $changes,
            ],
            summary: __('Assessment or certificate eligibility configuration changed.'),
        );
    }
}
