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

#[Fillable(['team_id', 'course_id', 'name', 'slug', 'package_type', 'currency', 'price', 'discount_price', 'validity_days', 'max_exam_attempts', 'includes_certificate', 'allows_free_preview', 'is_active', 'metadata'])]
class CoursePackage extends Model
{
    /** @use HasFactory<CoursePackageFactory> */
    use HasFactory, SoftDeletes;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

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
            'allows_free_preview' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
