<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLessonProgress
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Enrollment $enrollment, Lesson $lesson, array $data): LessonProgress
    {
        $lesson->loadMissing('courseModule');

        if ($lesson->courseModule->course_id !== $enrollment->course_id) {
            throw ValidationException::withMessages([
                'lesson_id' => __('The lesson does not belong to the enrolled course.'),
            ]);
        }

        $requestedProgressPercentage = min(100, max(0, (int) ($data['progress_percentage'] ?? 0)));
        $lastPositionSeconds = max(0, (int) ($data['last_position_seconds'] ?? 0));

        return DB::transaction(function () use ($enrollment, $lesson, $data, $requestedProgressPercentage, $lastPositionSeconds) {
            $progress = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->lockForUpdate()
                ->first();

            $progressPercentage = $progress instanceof LessonProgress
                ? max((int) $progress->progress_percentage, $requestedProgressPercentage)
                : $requestedProgressPercentage;
            $completedAt = $progress instanceof LessonProgress && $progress->completed_at !== null
                ? $progress->completed_at
                : ($progressPercentage >= 100 ? ($data['completed_at'] ?? now()) : null);
            $lessonContentRevision = $progress instanceof LessonProgress
                ? $progress->lesson_content_revision
                : (int) ($lesson->content_revision ?? 1);
            $event = $data['event'] ?? 'manual_progress_update';
            $metadata = $progress instanceof LessonProgress && is_array($progress->metadata)
                ? $progress->metadata
                : [];
            $events = is_array($metadata['events'] ?? null) ? $metadata['events'] : [];
            $metadata['event'] = $event;
            $metadata['events'] = collect($events)
                ->push([
                    'event' => $event,
                    'media_id' => $data['media_id'] ?? null,
                    'progress_percentage' => $progressPercentage,
                    'last_position_seconds' => $lastPositionSeconds,
                    'recorded_at' => now()->toISOString(),
                ])
                ->values()
                ->all();

            $attributes = [
                'lesson_content_revision' => $lessonContentRevision,
                'progress_percentage' => $progressPercentage,
                'last_position_seconds' => $lastPositionSeconds,
                'started_at' => $data['started_at'] ?? ($progress instanceof LessonProgress ? $progress->started_at : null) ?? now(),
                'completed_at' => $completedAt,
                'metadata' => $metadata,
            ];

            if ($progress) {
                $progress->update($attributes);

                return $progress->refresh();
            }

            return LessonProgress::create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                ...$attributes,
            ]);
        });
    }
}
