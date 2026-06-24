<?php

namespace App\Models;

use Database\Factories\FreePreviewEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'lead_id', 'user_id', 'course_id', 'lesson_id', 'event_type', 'source', 'campaign', 'session_id', 'ip_address', 'user_agent', 'occurred_at', 'metadata'])]
class FreePreviewEvent extends Model
{
    /** @use HasFactory<FreePreviewEventFactory> */
    use HasFactory;

    public const EVENT_STARTED = 'started';

    public const EVENT_COMPLETED = 'completed';

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
