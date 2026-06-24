<?php

namespace App\Actions\PowerX;

use App\Models\ExamAttempt;
use App\Models\Question;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SubmitExamAttempt
{
    /**
     * @param  array<int, array{question_id: int, answer: array<int, mixed>|string|int}>  $answers
     */
    public function handle(ExamAttempt $examAttempt, array $answers): ExamAttempt
    {
        $examAttempt->loadMissing(['exam', 'enrollment']);

        if ($examAttempt->submitted_at) {
            throw ValidationException::withMessages([
                'attempt_id' => __('This exam attempt has already been submitted.'),
            ]);
        }

        if ($examAttempt->started_at && $examAttempt->started_at->copy()->addMinutes($examAttempt->exam->duration_minutes + 5)->isPast()) {
            throw ValidationException::withMessages([
                'attempt_id' => __('This exam attempt has expired.'),
            ]);
        }

        $enrollment = $examAttempt->enrollment;

        if ($enrollment->status !== 'active' || $enrollment->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'enrollment_id' => __('The enrollment must be active and paid before submitting an exam.'),
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

        $submittedAnswers = collect($answers)
            ->mapWithKeys(fn (array $answer) => [
                (int) $answer['question_id'] => $this->normalizeAnswer($answer['answer']),
            ]);

        if ($submittedAnswers->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => __('At least one answer is required.'),
            ]);
        }

        $metadata = is_array($examAttempt->metadata) ? $examAttempt->metadata : [];
        $selectedQuestionIds = collect($this->arrayValue($metadata['selected_question_ids'] ?? []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedQuestionIds->isEmpty()) {
            $selectedQuestionIds = $submittedAnswers->keys()->map(fn (mixed $id): int => (int) $id)->values();
        }

        $unselectedQuestionIds = $submittedAnswers->keys()
            ->map(fn (mixed $id): int => (int) $id)
            ->diff($selectedQuestionIds);

        if ($unselectedQuestionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => __('One or more answers are not part of this exam attempt.'),
            ]);
        }

        $questions = Question::query()
            ->active()
            ->where('course_id', $examAttempt->exam->course_id)
            ->whereIn('id', $selectedQuestionIds)
            ->get(['id', 'correct_answer']);

        if ($questions->count() !== $selectedQuestionIds->count()) {
            throw ValidationException::withMessages([
                'answers' => __('One or more selected questions are no longer available for this exam.'),
            ]);
        }

        $correctAnswers = $questions->filter(
            fn (Question $question) => $submittedAnswers->get($question->id, []) === $this->normalizeAnswer($question->correct_answer),
        )->count();
        $score = round(($correctAnswers / $questions->count()) * 100, 2);

        $orderedAnswers = $selectedQuestionIds
            ->map(fn (int $questionId): array => [
                'question_id' => $questionId,
                'answer' => $submittedAnswers->get($questionId, []),
            ])
            ->values()
            ->all();
        unset($metadata['draft_answers']);

        $examAttempt->update([
            'answers' => $orderedAnswers,
            'score' => $score,
            'result' => $score >= $examAttempt->exam->pass_mark ? 'passed' : 'failed',
            'duration_seconds' => $examAttempt->started_at?->diffInSeconds(now()),
            'submitted_at' => now(),
            'metadata' => [
                ...$metadata,
                'correct_answers' => $correctAnswers,
                'total_questions' => $questions->count(),
            ],
        ]);

        return $examAttempt->refresh();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeAnswer(mixed $answer): array
    {
        return collect(Arr::wrap($answer))
            ->map(fn (mixed $value) => (string) $value)
            ->sort()
            ->values()
            ->all();
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
}
