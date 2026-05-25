<?php

namespace App\Actions\PowerX;

use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SubmitExamAttempt
{
    /**
     * @param  array<int, array{question_id: int, answer: array<int, mixed>|string|int}>  $answers
     */
    public function handle(ExamAttempt $examAttempt, array $answers): ExamAttempt
    {
        $examAttempt->loadMissing('exam');

        if ($examAttempt->submitted_at) {
            throw ValidationException::withMessages([
                'attempt_id' => __('This exam attempt has already been submitted.'),
            ]);
        }

        if ($examAttempt->started_at && $examAttempt->started_at->addMinutes($examAttempt->exam->duration_minutes + 5)->isPast()) {
            throw ValidationException::withMessages([
                'attempt_id' => __('This exam attempt has expired.'),
            ]);
        }

        $submittedAnswers = collect($answers)
            ->mapWithKeys(fn (array $answer) => [
                (int) $answer['question_id'] => $this->normalizeAnswer($answer['answer'] ?? []),
            ]);

        if ($submittedAnswers->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => __('At least one answer is required.'),
            ]);
        }

        $questions = Question::query()
            ->active()
            ->where('course_id', $examAttempt->exam->course_id)
            ->whereIn('id', $submittedAnswers->keys())
            ->get(['id', 'correct_answer']);

        if ($questions->count() !== $submittedAnswers->count()) {
            throw ValidationException::withMessages([
                'answers' => __('One or more answers do not belong to this exam course.'),
            ]);
        }

        $correctAnswers = $questions->filter(
            fn (Question $question) => $submittedAnswers->get($question->id) === $this->normalizeAnswer($question->correct_answer),
        )->count();
        $score = round(($correctAnswers / $questions->count()) * 100, 2);

        $examAttempt->update([
            'answers' => $submittedAnswers
                ->map(fn (array $answer, int $questionId) => [
                    'question_id' => $questionId,
                    'answer' => $answer,
                ])
                ->values()
                ->all(),
            'score' => $score,
            'result' => $score >= $examAttempt->exam->pass_mark ? 'passed' : 'failed',
            'duration_seconds' => $examAttempt->started_at?->diffInSeconds(now()),
            'submitted_at' => now(),
            'metadata' => [
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
}
