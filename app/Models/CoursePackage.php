<?php

namespace App\Models;

use Database\Factories\CoursePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['team_id', 'course_id', 'name', 'slug', 'package_type', 'currency', 'price', 'discount_price', 'validity_days', 'max_exam_attempts', 'includes_certificate', 'requires_lesson_completion_for_certificate', 'requires_exam_pass_for_certificate', 'requires_attendance_for_certificate', 'requires_practical_pass_for_certificate', 'allows_free_preview', 'is_active', 'metadata'])]
class CoursePackage extends Model
{
    /** @use HasFactory<CoursePackageFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'requires_lesson_completion_for_certificate' => true,
        'requires_exam_pass_for_certificate' => true,
        'requires_attendance_for_certificate' => false,
        'requires_practical_pass_for_certificate' => false,
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @param  Builder<CoursePackage>  $query
     * @return Builder<CoursePackage>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'includes_certificate' => 'boolean',
            'requires_lesson_completion_for_certificate' => 'boolean',
            'requires_exam_pass_for_certificate' => 'boolean',
            'requires_attendance_for_certificate' => 'boolean',
            'requires_practical_pass_for_certificate' => 'boolean',
            'allows_free_preview' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
