<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['course_module_id', 'title', 'slug', 'lesson_type', 'sort_order', 'duration_minutes', 'content', 'is_preview', 'is_active', 'metadata', 'content_revision', 'content_retired_at', 'replacement_lesson_id', 'content_retirement_note'])]
class Lesson extends Model implements HasMedia
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $attributes = [
        'content_revision' => 1,
    ];

    public function courseModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class);
    }

    public function replacementLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'replacement_lesson_id');
    }

    public function replacedLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'replacement_lesson_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

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
        $this->addMediaCollection('video')->singleFile()->useDisk('local');
        $this->addMediaCollection('learning-materials')->useDisk('local');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'content_revision' => 'integer',
            'content_retired_at' => 'datetime',
        ];
    }
}
