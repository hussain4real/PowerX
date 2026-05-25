<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartExamAttempt
{
    /**
     * Start a gated exam attempt for a paid active enrollment.
     */
    public function handle(Exam $exam, Enrollment $enrollment): ExamAttempt
    {
        $this->ensureEligible($exam, $enrollment);

        return DB::transaction(function () use ($exam, $enrollment) {
            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $attemptsUsed = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('enrollment_id', $lockedEnrollment->id)
                ->where('student_profile_id', $lockedEnrollment->student_profile_id)
                ->count();

            $maxAttempts = $lockedEnrollment->coursePackage?->max_exam_attempts ?? $exam->max_attempts;

            if ($attemptsUsed >= $maxAttempts) {
                throw ValidationException::withMessages([
                    'exam_id' => __('The maximum number of attempts has been reached.'),
                ]);
            }

            return ExamAttempt::create([
                'team_id' => $exam->team_id ?? $lockedEnrollment->team_id,
                'exam_id' => $exam->id,
                'enrollment_id' => $lockedEnrollment->id,
                'student_profile_id' => $lockedEnrollment->student_profile_id,
                'attempt_number' => $attemptsUsed + 1,
                'result' => 'pending',
                'started_at' => now(),
            ]);
        });
    }

    private function ensureEligible(Exam $exam, Enrollment $enrollment): void
    {
        if (! $exam->is_active) {
            throw ValidationException::withMessages([
                'exam_id' => __('This exam is not active.'),
            ]);
        }

        if ($exam->course_id !== $enrollment->course_id) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('The enrollment does not belong to this exam course.'),
            ]);
        }

        if ($enrollment->status !== 'active' || $enrollment->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'enrollment_id' => __('The enrollment must be active and paid before starting an exam.'),
            ]);
        }

        if ($enrollment->access_starts_at && $enrollment->access_starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has not started yet.'),
            ]);
        }

        if ($enrollment->access_expires_at && $enrollment->access_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has expired.'),
            ]);
        }
    }
}
