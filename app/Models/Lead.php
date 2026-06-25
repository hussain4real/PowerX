<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['team_id', 'owner_id', 'company_id', 'course_id', 'name', 'email', 'phone', 'source', 'campaign', 'status', 'course_interest', 'notes', 'follow_up_at', 'outcome', 'converted_at', 'metadata'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_ENROLLED = 'enrolled';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    public const STATUS_NOT_RESPONSIVE = 'not_responsive';

    public const STATUS_CONVERTED_LEGACY = 'converted';

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_PAYMENT_PENDING => 'Payment Pending',
            self::STATUS_ENROLLED => 'Enrolled',
            self::STATUS_WON => 'Won',
            self::STATUS_LOST => 'Lost',
            self::STATUS_NOT_RESPONSIVE => 'Not Responsive',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            'website' => 'Website',
            'ai_chat' => 'AI chat',
            'catalog' => 'Catalog',
            'course_detail' => 'Course detail',
            'whatsapp' => 'WhatsApp',
            'phone' => 'Phone',
            'referral' => 'Referral',
            'walk-in' => 'Walk-in',
            'paid_ads' => 'Paid ads',
            'email' => 'Email',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'campaign' => 'Campaign',
            'corporate' => 'Corporate inquiry',
            'public_registration' => 'Public registration',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function outcomeOptions(): array
    {
        return [
            'interested' => 'Interested',
            'registered' => 'Registered',
            'not_ready' => 'Not ready',
            'no_response' => 'No response',
            'disqualified' => 'Disqualified',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function freePreviewEvents(): HasMany
    {
        return $this->hasMany(FreePreviewEvent::class);
    }

    public function setMetadataAttribute(mixed $value): void
    {
        $this->attributes['metadata'] = json_encode($this->pruneMetadata($value));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    private function pruneMetadata(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $metadata = [];

        foreach ($value as $key => $item) {
            $cleaned = $this->pruneMetadata($item);

            if ($cleaned === null || $cleaned === '' || $cleaned === []) {
                continue;
            }

            $metadata[$key] = $cleaned;
        }

        return $metadata;
    }
}
