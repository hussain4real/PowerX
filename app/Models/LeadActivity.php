<?php

namespace App\Models;

use Database\Factories\LeadActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'lead_id', 'actor_id', 'type', 'title', 'notes', 'channel', 'previous_status', 'next_status', 'follow_up_at', 'metadata'])]
class LeadActivity extends Model
{
    /** @use HasFactory<LeadActivityFactory> */
    use HasFactory;

    public const TYPE_CONTACT_LOGGED = 'contact_logged';

    public const TYPE_QUALIFIED = 'qualified';

    public const TYPE_QUOTATION_SENT = 'quotation_sent';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_CONVERTED = 'converted';

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
