<?php

namespace App\Actions\PowerX;

use App\Models\AuditEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RecordAuditEvent
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        string $action,
        Model $subject,
        ?User $actor = null,
        ?Team $team = null,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
        ?string $summary = null,
        ?Request $request = null,
    ): AuditEvent {
        $request ??= request();

        return AuditEvent::create([
            'team_id' => $team?->id ?? $subject->getAttribute('team_id'),
            'actor_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'summary' => $summary,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
