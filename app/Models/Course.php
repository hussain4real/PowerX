<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property Carbon|null $published_at
 * @property Carbon|null $content_retired_at
 * @property array<string, mixed>|null $metadata
 */
#[Fillable(['team_id', 'title', 'slug', 'category', 'status', 'delivery_mode', 'currency', 'base_price', 'validity_days', 'requires_lesson_completion_for_certificate', 'requires_exam_pass_for_certificate', 'requires_attendance_for_certificate', 'requires_practical_pass_for_certificate', 'summary', 'description', 'is_featured', 'metadata', 'published_at', 'content_revision', 'content_retired_at', 'replacement_course_id', 'content_retirement_note'])]
class Course extends Model implements HasMedia
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $attributes = [
        'content_revision' => 1,
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
     * @return HasMany<CoursePackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(CoursePackage::class);
    }

    /**
     * @return HasMany<CourseModule, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<TrainingBatch, $this>
     */
    public function trainingBatches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function replacementCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'replacement_course_id');
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function replacedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'replacement_course_id');
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeContentRetired(Builder $query): Builder
    {
        return $query->whereNotNull('content_retired_at');
    }

    public function isContentRetired(): bool
    {
        return $this->content_retired_at !== null && $this->content_retired_at->isPast();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover-image')->singleFile()->useDisk('local');
        $this->addMediaCollection('syllabus')->singleFile()->useDisk('local');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'content_revision' => 'integer',
            'is_featured' => 'boolean',
            'requires_lesson_completion_for_certificate' => 'boolean',
            'requires_exam_pass_for_certificate' => 'boolean',
            'requires_attendance_for_certificate' => 'boolean',
            'requires_practical_pass_for_certificate' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'content_retired_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
