<?php

namespace App\Actions\PowerX;

use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;

class BuildInstructorPortal
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $instructor, Team $team): array
    {
        $batches = TrainingBatch::query()
            ->whereBelongsTo($team)
            ->where('instructor_id', $instructor->id)
            ->with([
                'course.modules' => fn ($query) => $query->active()->orderBy('sort_order'),
                'course.modules.lessons' => fn ($query) => $query->active()->orderBy('sort_order'),
                'sessions' => fn ($query) => $query->orderBy('starts_at'),
                'sessions.attendanceRecords.enrollment.studentProfile',
            ])
            ->orderBy('starts_at')
            ->get();

        $portalBatches = $batches->map(fn (TrainingBatch $batch): array => $this->batchPayload($batch));
        $sessions = $portalBatches->flatMap(fn (array $batch): array => $batch['sessions']);
        $students = $sessions->flatMap(fn (array $session): array => $session['students']);
        $nextSession = $sessions
            ->filter(fn (array $session): bool => $session['startsAt'] !== null && $session['startsAt'] >= now()->toISOString())
            ->sortBy('startsAt')
            ->first();

        return [
            'summary' => [
                'assignedBatches' => $portalBatches->count(),
                'scheduledSessions' => $sessions->count(),
                'students' => $students->pluck('enrollmentId')->unique()->count(),
                'pendingAttendance' => $students->where('attendanceStatus', 'pending')->count(),
                'pendingPractical' => $students->whereNull('practicalOutcome')->count(),
                'nextSessionLabel' => $nextSession['title'] ?? 'No upcoming assigned session',
            ],
            'batches' => $portalBatches->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function batchPayload(TrainingBatch $batch): array
    {
        $attendanceRecords = $batch->sessions->flatMap->attendanceRecords;
        $attendanceTotal = $attendanceRecords->count();
        $attendancePresent = $attendanceRecords->whereIn('status', ['present', 'late'])->count();

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'status' => $batch->status,
            'deliveryMode' => $batch->delivery_mode,
            'venue' => $batch->venue,
            'capacity' => $batch->capacity,
            'startsAt' => $batch->starts_at?->toISOString(),
            'endsAt' => $batch->ends_at?->toISOString(),
            'attendanceRate' => $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
            'course' => [
                'id' => $batch->course->id,
                'title' => $batch->course->title,
                'category' => $batch->course->category,
                'deliveryMode' => $batch->course->delivery_mode,
                'url' => route('courses.show', ['course' => $batch->course]),
            ],
            'sessions' => $batch->sessions->map(fn ($session): array => $this->sessionPayload($session))->values()->all(),
            'resources' => $batch->course->modules
                ->map(fn ($module): array => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'lessons' => $module->lessons
                        ->map(fn ($lesson): array => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'lessonType' => $lesson->lesson_type,
                            'durationMinutes' => $lesson->duration_minutes,
                            'isPreview' => $lesson->is_preview,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(TrainingSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'sessionType' => $session->session_type,
            'venue' => $session->venue,
            'status' => $session->status,
            'startsAt' => $session->starts_at?->toISOString(),
            'endsAt' => $session->ends_at?->toISOString(),
            'students' => $session->attendanceRecords
                ->sortBy(fn ($attendance): string => $attendance->enrollment->studentProfile->full_name)
                ->map(fn ($attendance): array => [
                    'attendanceId' => $attendance->id,
                    'enrollmentId' => $attendance->enrollment_id,
                    'studentProfileId' => $attendance->enrollment->studentProfile->id,
                    'studentName' => $attendance->enrollment->studentProfile->full_name,
                    'studentEmail' => $attendance->enrollment->studentProfile->email,
                    'studentMobile' => $attendance->enrollment->studentProfile->mobile,
                    'attendanceStatus' => $attendance->status,
                    'attendedAt' => $attendance->attended_at?->toISOString(),
                    'practicalOutcome' => $attendance->practical_outcome,
                    'practicalScore' => $attendance->practical_score !== null ? (float) $attendance->practical_score : null,
                    'practicalComments' => $attendance->practical_comments,
                ])
                ->values()
                ->all(),
        ];
    }
}
