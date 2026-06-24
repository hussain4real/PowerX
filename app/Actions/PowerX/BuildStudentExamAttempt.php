<?php

namespace App\Actions\PowerX;

use App\Models\ExamAttempt;
use App\Models\Question;
use Carbon\CarbonInterface;

class BuildStudentExamAttempt
{
    /**
     * @return array<string, mixed>
     */
    public function handle(ExamAttempt $examAttempt): array
    {
        $examAttempt->loadMissing(['team', 'exam.course', 'enrollment.course', 'studentProfile']);

        $metadata = is_array($examAttempt->metadata) ? $examAttempt->metadata : [];
        $answers = is_array($examAttempt->answers) ? $examAttempt->answers : [];
        $selectedQuestionIds = collect($this->arrayValue($metadata['selected_question_ids'] ?? []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedQuestionIds->isEmpty()) {
            $selectedQuestionIds = collect($answers)
                ->pluck('question_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values();
        }

        $selectedQuestionOrder = array_flip($selectedQuestionIds->all());
        $questions = Question::query()
            ->active()
            ->where('course_id', $examAttempt->exam->course_id)
            ->whereIn('id', $selectedQuestionIds)
            ->get()
            ->sortBy(fn (Question $question): int => $selectedQuestionOrder[$question->id] ?? PHP_INT_MAX)
            ->values();
        $answerPayload = $examAttempt->submitted_at
            ? $this->answersPayload($answers)
            : $this->answersPayload($this->arrayValue($metadata['draft_answers'] ?? []));

        return [
            'profile' => [
                'id' => $examAttempt->studentProfile->id,
                'fullName' => $examAttempt->studentProfile->full_name,
                'email' => $examAttempt->studentProfile->email,
            ],
            'attempt' => [
                'id' => $examAttempt->id,
                'attemptNumber' => $examAttempt->attempt_number,
                'result' => $examAttempt->result,
                'score' => $examAttempt->score,
                'durationSeconds' => $examAttempt->duration_seconds,
                'startedAt' => $this->dateTimeToIsoString($examAttempt->started_at),
                'expiresAt' => $this->expiresAt($examAttempt),
                'submittedAt' => $this->dateTimeToIsoString($examAttempt->submitted_at),
                'isSubmitted' => $examAttempt->submitted_at !== null,
                'autosaveUrl' => route('student.exam-attempts.update', [
                    'current_team' => $examAttempt->team,
                    'examAttempt' => $examAttempt,
                ]),
                'submitUrl' => route('student.exam-attempts.submit', [
                    'current_team' => $examAttempt->team,
                    'examAttempt' => $examAttempt,
                ]),
            ],
            'enrollment' => [
                'id' => $examAttempt->enrollment->id,
                'courseTitle' => $examAttempt->enrollment->course->title,
                'courseSlug' => $examAttempt->enrollment->course->slug,
            ],
            'exam' => [
                'id' => $examAttempt->exam->id,
                'title' => $examAttempt->exam->title,
                'examType' => $examAttempt->exam->exam_type,
                'durationMinutes' => $examAttempt->exam->duration_minutes,
                'passMark' => $examAttempt->exam->pass_mark,
                'questionCount' => $questions->count(),
            ],
            'questions' => $questions
                ->map(fn (Question $question): array => [
                    'id' => $question->id,
                    'topic' => $question->topic,
                    'difficulty' => $question->difficulty,
                    'type' => $question->type,
                    'questionText' => $question->question_text,
                    'options' => $question->options,
                    'answer' => $answerPayload[$question->id] ?? [],
                    'explanation' => $examAttempt->submitted_at ? $question->explanation : null,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, mixed>  $answers
     * @return array<int, array<int, string>>
     */
    private function answersPayload(array $answers): array
    {
        $payload = [];

        foreach ($answers as $answer) {
            if (! is_array($answer) || ! array_key_exists('question_id', $answer)) {
                continue;
            }

            $value = $answer['answer'] ?? [];
            $values = is_array($value) ? $value : [$value];

            $payload[(int) $answer['question_id']] = array_values(array_map(
                fn (mixed $item): string => (string) $item,
                $values,
            ));
        }

        return $payload;
    }

    /**
     * @return array<int, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function dateTimeToIsoString(mixed $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toISOString() : null;
    }

    private function expiresAt(ExamAttempt $examAttempt): ?string
    {
        if (! $examAttempt->started_at instanceof CarbonInterface) {
            return null;
        }

        return $examAttempt->started_at
            ->copy()
            ->addMinutes((int) $examAttempt->exam->duration_minutes)
            ->toISOString();
    }
}
