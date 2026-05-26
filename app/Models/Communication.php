<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CommunicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'team_id',
    'lead_id',
    'student_profile_id',
    'company_id',
    'user_id',
    'channel',
    'template_key',
    'subject',
    'message',
    'status',
    'scheduled_at',
    'queued_at',
    'sent_at',
    'delivered_at',
    'failed_at',
    'retry_at',
    'retry_count',
    'failure_reason',
    'opted_out_at',
    'opt_out_reason',
    'metadata',
])]
class Communication extends Model
{
    /** @use HasFactory<CommunicationFactory> */
    use HasFactory, SoftDeletes;

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PHONE = 'phone';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FAILED = 'failed';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_RETRY = 'retry';

    public const STATUS_OPTED_OUT = 'opted_out';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'channel' => self::CHANNEL_EMAIL,
        'status' => self::STATUS_DRAFT,
        'retry_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    public static function channelOptions(): array
    {
        return [
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_PHONE => 'Phone',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_RETRY => 'Retry',
            self::STATUS_OPTED_OUT => 'Opted out',
        ];
    }

    public function scopeReadyForDelivery(Builder $query, ?CarbonInterface $asOf = null): Builder
    {
        $asOf ??= now();

        return $query
            ->whereNull('opted_out_at')
            ->where(function (Builder $query) use ($asOf): void {
                $query
                    ->where(function (Builder $query) use ($asOf): void {
                        $query
                            ->where('status', self::STATUS_SCHEDULED)
                            ->where(function (Builder $query) use ($asOf): void {
                                $query
                                    ->whereNull('scheduled_at')
                                    ->orWhere('scheduled_at', '<=', $asOf);
                            });
                    })
                    ->orWhere(function (Builder $query) use ($asOf): void {
                        $query
                            ->where('status', self::STATUS_RETRY)
                            ->where(function (Builder $query) use ($asOf): void {
                                $query
                                    ->whereNull('retry_at')
                                    ->orWhere('retry_at', '<=', $asOf);
                            });
                    });
            });
    }

    public function markQueued(?CarbonInterface $queuedAt = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_QUEUED,
            'queued_at' => $queuedAt ?? now(),
        ])->save();
    }

    public function markDelivered(?CarbonInterface $deliveredAt = null): bool
    {
        $deliveredAt ??= now();

        return $this->forceFill([
            'status' => self::STATUS_DELIVERED,
            'sent_at' => $this->sent_at ?? $deliveredAt,
            'delivered_at' => $deliveredAt,
        ])->save();
    }

    public function markFailed(string $failureReason, ?CarbonInterface $failedAt = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failed_at' => $failedAt ?? now(),
            'failure_reason' => $failureReason,
            'retry_at' => null,
        ])->save();
    }

    public function scheduleRetry(CarbonInterface $retryAt, ?string $failureReason = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_RETRY,
            'retry_at' => $retryAt,
            'retry_count' => ((int) $this->retry_count) + 1,
            'failure_reason' => $failureReason ?? $this->failure_reason,
        ])->save();
    }

    public function markOptedOut(?CarbonInterface $optedOutAt = null, ?string $reason = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_OPTED_OUT,
            'opted_out_at' => $optedOutAt ?? now(),
            'opt_out_reason' => $reason,
        ])->save();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'retry_at' => 'datetime',
            'retry_count' => 'integer',
            'opted_out_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
