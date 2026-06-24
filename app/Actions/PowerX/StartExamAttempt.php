<?php

namespace App\Actions\PowerX;

use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartExamAttempt
{
    /**
     * Start a gated exam attempt for a paid active enrollment.
     */
    public function handle(Exam $exam, Enrollment $enrollment): ExamAttempt
    {
        return DB::transaction(function () use ($exam, $enrollment) {
            $exam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedEnrollment = Enrollment::query()
                ->with('coursePackage')
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureEligible($exam, $lockedEnrollment);

            $attemptsUsed = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('enrollment_id', $lockedEnrollment->id)
                ->where('student_profile_id', $lockedEnrollment->student_profile_id)
                ->count();

            $coursePackage = $this->coursePackage($lockedEnrollment);
            $maxAttempts = $coursePackage === null
                ? $exam->max_attempts
                : ($coursePackage->max_exam_attempts ?? $exam->max_attempts);

            if ($attemptsUsed >= $maxAttempts) {
                throw ValidationException::withMessages([
                    'exam_id' => __('The maximum number of attempts has been reached.'),
                ]);
            }

            $questionIds = $this->selectedQuestionIds($exam);

            return ExamAttempt::create([
                'team_id' => $exam->team_id ?? $lockedEnrollment->team_id,
                'exam_id' => $exam->id,
                'enrollment_id' => $lockedEnrollment->id,
                'student_profile_id' => $lockedEnrollment->student_profile_id,
                'attempt_number' => $attemptsUsed + 1,
                'result' => 'pending',
                'started_at' => now(),
                'metadata' => [
                    'selected_question_ids' => $questionIds,
                    'selected_question_count' => count($questionIds),
                    'question_count_requested' => $exam->question_count,
                    'randomized' => $exam->randomize_questions,
                    'duration_minutes' => $exam->duration_minutes,
                ],
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

        if ($this->dateTimeIsFuture($enrollment->access_starts_at)) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has not started yet.'),
            ]);
        }

        if ($this->dateTimeIsPast($enrollment->access_expires_at)) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has expired.'),
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function selectedQuestionIds(Exam $exam): array
    {
        $metadata = is_array($exam->metadata) ? $exam->metadata : [];
        $query = Question::query()
            ->active()
            ->where('course_id', $exam->course_id);

        $topics = collect($this->arrayValue($metadata['question_topics'] ?? $metadata['topic_filters'] ?? []))
            ->filter()
            ->values();
        $difficulties = collect($this->arrayValue($metadata['question_difficulties'] ?? $metadata['difficulty_filters'] ?? []))
            ->filter()
            ->values();

        if ($topics->isNotEmpty()) {
            $query->whereIn('topic', $topics);
        }

        if ($difficulties->isNotEmpty()) {
            $query->whereIn('difficulty', $difficulties);
        }

        if ($exam->randomize_questions) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('id');
        }

        if ($exam->question_count) {
            $query->limit($exam->question_count);
        }

        $questionIds = $query->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'exam_id' => __('This exam has no active questions available.'),
            ]);
        }

        return $questionIds;
    }

    /**
     * @return array<int, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function dateTimeIsFuture(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isFuture();
    }

    private function dateTimeIsPast(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isPast();
    }

    private function coursePackage(Enrollment $enrollment): ?CoursePackage
    {
        if ($enrollment->course_package_id === null) {
            return null;
        }

        return $enrollment->coursePackage;
    }
}
