<?php

namespace App\Models;

use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['team_id', 'enrollment_id', 'invoice_id', 'company_id', 'student_profile_id', 'approved_by_id', 'method', 'provider', 'reference', 'status', 'currency', 'amount', 'paid_at', 'approved_at', 'metadata'])]
class PaymentTransaction extends Model implements HasMedia
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_CHEQUE = 'cheque';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_INFORMATION_REQUESTED = 'information_requested';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_VOIDED = 'voided';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_ADJUSTED = 'adjusted';

    /**
     * @return array<string, string>
     */
    public static function manualMethodOptions(): array
    {
        return config('powerx_payments.manual.methods', [
            self::METHOD_BANK_TRANSFER => 'Bank transfer',
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHEQUE => 'Cheque',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_INFORMATION_REQUESTED => 'Information requested',
            self::STATUS_DUPLICATE => 'Duplicate',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_VOIDED => 'Voided',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_ADJUSTED => 'Adjusted',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reviewableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_INFORMATION_REQUESTED,
            self::STATUS_PARTIAL,
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<StudentProfile, $this>
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * @param  Builder<PaymentTransaction>  $query
     * @return Builder<PaymentTransaction>
     */
    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param  Builder<PaymentTransaction>  $query
     * @return Builder<PaymentTransaction>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('payment-proofs')->useDisk('local');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
