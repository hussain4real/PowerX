<?php

namespace App\Actions\PowerX;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BuildExamAnalytics
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Team $team): array
    {
        $attempts = ExamAttempt::query()
            ->whereBelongsTo($team)
            ->whereNotNull('submitted_at')
            ->with([
                'exam:id,course_id,title,exam_type',
                'exam.course:id,title',
                'enrollment:id,student_profile_id,course_id',
                'enrollment.studentProfile:id,full_name',
                'enrollment.attendanceRecords:id,enrollment_id,training_session_id,status',
                'enrollment.attendanceRecords.trainingSession:id,training_batch_id,title',
                'enrollment.attendanceRecords.trainingSession.trainingBatch:id,course_id,instructor_id,name',
                'enrollment.attendanceRecords.trainingSession.trainingBatch.instructor:id,name',
                'studentProfile:id,full_name',
            ])
            ->select([
                'id',
                'team_id',
                'exam_id',
                'enrollment_id',
                'student_profile_id',
                'attempt_number',
                'result',
                'score',
                'duration_seconds',
                'answers',
                'submitted_at',
                'metadata',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $questions = $this->questionsForAttempts($team, $attempts);
        $questionAnalytics = $this->questionAnalytics($attempts, $questions);
        $weakTopics = $this->weakTopicAnalytics($attempts, $questionAnalytics);

        return [
            'summary' => $this->summary($attempts, $weakTopics),
            'courses' => $this->courseAnalytics($attempts),
            'exams' => $this->examAnalytics($attempts),
            'students' => $this->studentAnalytics($attempts),
            'batches' => $this->batchAnalytics($attempts),
            'instructors' => $this->instructorAnalytics($attempts),
            'questions' => $questionAnalytics->values()->all(),
            'weakTopics' => $weakTopics->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @param  Collection<int, array<string, mixed>>  $weakTopics
     * @return array<string, int|float|null|string>
     */
    private function summary(Collection $attempts, Collection $weakTopics): array
    {
        $passedAttempts = $attempts->where('result', 'passed')->count();
        $failedAttempts = $attempts->where('result', 'failed')->count();
        $topWeakTopic = $weakTopics->first();

        return [
            'total_attempts' => $attempts->count(),
            'submitted_attempts' => $attempts->count(),
            'passed_attempts' => $passedAttempts,
            'failed_attempts' => $failedAttempts,
            'pass_rate' => $this->percentage($passedAttempts, $attempts->count()),
            'average_score' => $this->averageScore($attempts),
            'average_duration_minutes' => $this->averageDurationMinutes($attempts),
            'weak_topic_count' => $weakTopics->count(),
            'top_weak_topic' => $topWeakTopic['topic'] ?? null,
            'top_weak_topic_occurrences' => $topWeakTopic['occurrences'] ?? 0,
        ];
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function courseAnalytics(Collection $attempts): array
    {
        return $attempts
            ->groupBy(fn (ExamAttempt $attempt): string => (string) ($attempt->exam?->course_id ?? 'unassigned'))
            ->map(function (Collection $group): array {
                $firstAttempt = $group->first();
                $passed = $group->where('result', 'passed')->count();

                return [
                    'courseId' => $firstAttempt?->exam?->course_id,
                    'courseTitle' => $firstAttempt?->exam?->course?->title ?? 'Not linked',
                    'attempts' => $group->count(),
                    'passed' => $passed,
                    'failed' => $group->where('result', 'failed')->count(),
                    'passRate' => $this->percentage($passed, $group->count()),
                    'averageScore' => $this->averageScore($group),
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function examAnalytics(Collection $attempts): array
    {
        return $attempts
            ->groupBy('exam_id')
            ->map(function (Collection $group): array {
                $firstAttempt = $group->first();
                $passed = $group->where('result', 'passed')->count();

                return [
                    'examId' => $firstAttempt?->exam_id,
                    'examTitle' => $firstAttempt?->exam?->title ?? 'Not linked',
                    'courseTitle' => $firstAttempt?->exam?->course?->title ?? 'Not linked',
                    'attempts' => $group->count(),
                    'passed' => $passed,
                    'failed' => $group->where('result', 'failed')->count(),
                    'passRate' => $this->percentage($passed, $group->count()),
                    'averageScore' => $this->averageScore($group),
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function studentAnalytics(Collection $attempts): array
    {
        return $attempts
            ->groupBy('student_profile_id')
            ->map(function (Collection $group): array {
                $firstAttempt = $group->first();

                return [
                    'studentProfileId' => $firstAttempt?->student_profile_id,
                    'studentName' => $firstAttempt?->studentProfile?->full_name
                        ?? $firstAttempt?->enrollment?->studentProfile?->full_name
                        ?? 'Not linked',
                    'attempts' => $group->count(),
                    'bestScore' => (float) $group->max('score'),
                    'latestResult' => $group->sortByDesc('submitted_at')->first()?->result,
                    'passedAttempts' => $group->where('result', 'passed')->count(),
                    'failedAttempts' => $group->where('result', 'failed')->count(),
                ];
            })
            ->sortByDesc('bestScore')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function batchAnalytics(Collection $attempts): array
    {
        return $this->deliveryGroupAnalytics($attempts, 'batch');
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function instructorAnalytics(Collection $attempts): array
    {
        return $this->deliveryGroupAnalytics($attempts, 'instructor');
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return array<int, array<string, mixed>>
     */
    private function deliveryGroupAnalytics(Collection $attempts, string $dimension): array
    {
        return $attempts
            ->flatMap(fn (ExamAttempt $attempt): Collection => $this->deliveryGroupsForAttempt($attempt, $dimension)
                ->map(fn (array $deliveryGroup): array => [
                    'attempt' => $attempt,
                    'deliveryGroup' => $deliveryGroup,
                ]))
            ->groupBy(fn (array $row): string => (string) $row['deliveryGroup']['id'])
            ->map(function (Collection $group) use ($dimension): array {
                $firstRow = $group->first();
                $groupAttempts = $group->pluck('attempt');
                $passed = $groupAttempts->where('result', 'passed')->count();

                return [
                    $dimension.'Id' => $firstRow['deliveryGroup']['id'],
                    $dimension.'Name' => $firstRow['deliveryGroup']['name'],
                    'courseTitle' => $firstRow['attempt']->exam?->course?->title ?? 'Not linked',
                    'attempts' => $groupAttempts->count(),
                    'passed' => $passed,
                    'failed' => $groupAttempts->where('result', 'failed')->count(),
                    'passRate' => $this->percentage($passed, $groupAttempts->count()),
                    'averageScore' => $this->averageScore($groupAttempts),
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: int|string, name: string}>
     */
    private function deliveryGroupsForAttempt(ExamAttempt $attempt, string $dimension): Collection
    {
        $courseId = $attempt->exam?->course_id;
        $attendanceRecords = $attempt->enrollment?->attendanceRecords ?? collect();
        $groups = $attendanceRecords
            ->filter(fn ($attendanceRecord): bool => $attendanceRecord->trainingSession?->trainingBatch !== null
                && $attendanceRecord->trainingSession->trainingBatch->course_id === $courseId)
            ->map(function ($attendanceRecord) use ($dimension): array {
                $batch = $attendanceRecord->trainingSession->trainingBatch;

                if ($dimension === 'instructor') {
                    return [
                        'id' => $batch->instructor_id ?: 'unassigned',
                        'name' => $batch->instructor?->name ?? 'Unassigned instructor',
                    ];
                }

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();

        if ($groups->isNotEmpty()) {
            return $groups;
        }

        return collect([[
            'id' => 'unassigned',
            'name' => $dimension === 'instructor' ? 'Unassigned instructor' : 'Unassigned batch',
        ]]);
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return Collection<int, Question>
     */
    private function questionsForAttempts(Team $team, Collection $attempts): Collection
    {
        $questionIds = $attempts
            ->flatMap(fn (ExamAttempt $attempt): Collection => $this->answers($attempt)->pluck('question_id'))
            ->filter()
            ->unique()
            ->values();

        if ($questionIds->isEmpty()) {
            return collect();
        }

        return Question::query()
            ->whereBelongsTo($team)
            ->whereIn('id', $questionIds)
            ->select(['id', 'course_id', 'topic', 'difficulty', 'question_text', 'correct_answer'])
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, array<string, mixed>>
     */
    private function questionAnalytics(Collection $attempts, Collection $questions): Collection
    {
        $stats = collect();

        foreach ($attempts as $attempt) {
            foreach ($this->answers($attempt) as $answer) {
                $questionId = $answer['question_id'] ?? null;
                $question = $questionId ? $questions->get($questionId) : null;

                if (! $question) {
                    continue;
                }

                $key = (string) $question->id;
                $current = $stats->get($key, [
                    'questionId' => $question->id,
                    'topic' => $question->topic ?: 'Not set',
                    'difficulty' => $question->difficulty,
                    'questionText' => $question->question_text,
                    'attempts' => 0,
                    'correctAnswers' => 0,
                    'incorrectAnswers' => 0,
                    'accuracyRate' => 0.0,
                ]);
                $isCorrect = $this->answerIsCorrect($answer, $question);
                $current['attempts']++;
                $current[$isCorrect ? 'correctAnswers' : 'incorrectAnswers']++;
                $current['accuracyRate'] = $this->percentage($current['correctAnswers'], $current['attempts']);
                $stats->put($key, $current);
            }
        }

        return $stats->sortBy([
            ['incorrectAnswers', 'desc'],
            ['attempts', 'desc'],
        ]);
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @param  Collection<int, array<string, mixed>>  $questionAnalytics
     * @return Collection<int, array<string, mixed>>
     */
    private function weakTopicAnalytics(Collection $attempts, Collection $questionAnalytics): Collection
    {
        $topics = collect();

        foreach ($attempts as $attempt) {
            $topic = data_get($attempt->metadata ?? [], 'weak_topic');

            if (filled($topic)) {
                $this->incrementTopic($topics, (string) $topic, $attempt);
            }
        }

        foreach ($questionAnalytics as $question) {
            if ((int) $question['incorrectAnswers'] === 0) {
                continue;
            }

            $current = $topics->get($question['topic'], [
                'topic' => $question['topic'],
                'occurrences' => 0,
                'averageScore' => 0.0,
                'questionCount' => 0,
            ]);
            $current['occurrences'] += (int) $question['incorrectAnswers'];
            $current['questionCount']++;
            $topics->put($question['topic'], $current);
        }

        return $topics
            ->sortByDesc('occurrences')
            ->values();
    }

    private function incrementTopic(Collection $topics, string $topic, ExamAttempt $attempt): void
    {
        $current = $topics->get($topic, [
            'topic' => $topic,
            'occurrences' => 0,
            'averageScore' => 0.0,
            'questionCount' => 0,
            'scoreTotal' => 0.0,
        ]);
        $current['occurrences']++;
        $current['scoreTotal'] = (float) ($current['scoreTotal'] ?? 0) + (float) $attempt->score;
        $current['averageScore'] = round($current['scoreTotal'] / $current['occurrences'], 1);
        $topics->put($topic, $current);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function answers(ExamAttempt $attempt): Collection
    {
        return collect($attempt->answers ?? [])
            ->filter(fn (mixed $answer): bool => is_array($answer) && array_key_exists('question_id', $answer))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    private function answerIsCorrect(array $answer, Question $question): bool
    {
        if (array_key_exists('is_correct', $answer)) {
            return (bool) $answer['is_correct'];
        }

        return $this->normalizeAnswer($answer['answer'] ?? null) === $this->normalizeAnswer($question->correct_answer);
    }

    private function normalizeAnswer(mixed $answer): string
    {
        $values = collect(Arr::wrap($answer))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->sort()
            ->values()
            ->all();

        return implode('|', $values);
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     */
    private function averageScore(Collection $attempts): float
    {
        return $attempts->whereNotNull('score')->isNotEmpty()
            ? round((float) $attempts->whereNotNull('score')->avg('score'), 1)
            : 0.0;
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     */
    private function averageDurationMinutes(Collection $attempts): float
    {
        return $attempts->whereNotNull('duration_seconds')->isNotEmpty()
            ? round((float) $attempts->whereNotNull('duration_seconds')->avg('duration_seconds') / 60, 1)
            : 0.0;
    }

    private function percentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }
}
