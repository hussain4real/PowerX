<?php

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('students can start an exam and see a stable selected question set', function (): void {
    $this->withoutVite();

    [$user, $team, $enrollment, $exam, $questions] = studentExamAttemptFixture([
        'question_count' => 2,
        'randomize_questions' => true,
    ]);

    $this
        ->actingAs($user)
        ->post(route('student.exam-attempts.store', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'exam' => $exam,
        ]))
        ->assertRedirect();

    $attempt = ExamAttempt::query()->sole();
    $selectedQuestionIds = $attempt->metadata['selected_question_ids'];

    expect($selectedQuestionIds)->toHaveCount(2)
        ->and($selectedQuestionIds)->each->toBeIn($questions->pluck('id')->all());

    $this
        ->actingAs($user)
        ->get(route('student.exam-attempts.show', [
            'current_team' => $team,
            'examAttempt' => $attempt,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/ExamAttempt')
            ->where('exam.title', 'Kahramaa Mock')
            ->where('attempt.isSubmitted', false)
            ->has('questions', 2)
            ->where('questions.0.id', $selectedQuestionIds[0])
            ->missing('questions.0.correctAnswer'));

    $this
        ->actingAs($user)
        ->get(route('student.exam-attempts.show', [
            'current_team' => $team,
            'examAttempt' => $attempt->fresh(),
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('questions.0.id', $selectedQuestionIds[0])
            ->where('questions.1.id', $selectedQuestionIds[1]));
});

test('students can autosave and submit exam attempts for scored results', function (): void {
    $this->withoutVite();

    [$user, $team, $enrollment, $exam] = studentExamAttemptFixture([
        'question_count' => 2,
        'randomize_questions' => false,
        'max_attempts' => 1,
    ]);

    $this
        ->actingAs($user)
        ->post(route('student.exam-attempts.store', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'exam' => $exam,
        ]))
        ->assertRedirect();

    $attempt = ExamAttempt::query()->sole();
    $answers = collect($attempt->metadata['selected_question_ids'])
        ->map(fn (int $questionId): array => [
            'question_id' => $questionId,
            'answer' => [$questionId === $attempt->metadata['selected_question_ids'][0] ? 'A' : 'B'],
        ])
        ->values()
        ->all();

    $this
        ->actingAs($user)
        ->patch(route('student.exam-attempts.update', [
            'current_team' => $team,
            'examAttempt' => $attempt,
        ]), ['answers' => [$answers[0]]])
        ->assertRedirect();

    expect($attempt->fresh()->metadata['draft_answers'][0]['question_id'])->toBe($answers[0]['question_id']);

    $this
        ->actingAs($user)
        ->post(route('student.exam-attempts.submit', [
            'current_team' => $team,
            'examAttempt' => $attempt,
        ]), ['answers' => $answers])
        ->assertRedirect(route('student.exam-attempts.show', [
            'current_team' => $team,
            'examAttempt' => $attempt,
        ]));

    $attempt->refresh();

    expect($attempt->result)->toBe('passed')
        ->and($attempt->score)->toBe('100.00')
        ->and($attempt->answers)->toHaveCount(2)
        ->and($attempt->metadata)->not->toHaveKey('draft_answers');

    $this
        ->actingAs($user)
        ->get(route('student.exam-attempts.show', [
            'current_team' => $team,
            'examAttempt' => $attempt,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/ExamAttempt')
            ->where('attempt.isSubmitted', true)
            ->where('attempt.result', 'passed')
            ->where('questions.0.explanation', 'Use approved isolation sequence.'));

    $this
        ->actingAs($user)
        ->get(route('student.exams.index', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('enrollments.0.exams.0.attemptsUsed', 1)
            ->where('enrollments.0.exams.0.lastResult', 'passed')
            ->where('enrollments.0.exams.0.canStart', false));
});

test('students cannot start or submit exams outside active paid access', function (): void {
    [$user, $team, $enrollment, $exam] = studentExamAttemptFixture([
        'question_count' => 1,
        'randomize_questions' => false,
    ], [
        'status' => Enrollment::STATUS_PENDING,
        'payment_status' => 'pending',
    ]);

    $this
        ->actingAs($user)
        ->post(route('student.exam-attempts.store', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'exam' => $exam,
        ]))
        ->assertForbidden();

    [$activeUser, $activeTeam, $activeEnrollment, $activeExam] = studentExamAttemptFixture([
        'question_count' => 1,
        'randomize_questions' => false,
    ]);

    $this
        ->actingAs($activeUser)
        ->post(route('student.exam-attempts.store', [
            'current_team' => $activeTeam,
            'enrollment' => $activeEnrollment,
            'exam' => $activeExam,
        ]));

    $attempt = ExamAttempt::query()->latest('id')->firstOrFail();
    $activeEnrollment->update(['access_expires_at' => now()->subMinute()]);

    $this
        ->actingAs($activeUser)
        ->post(route('student.exam-attempts.submit', [
            'current_team' => $activeTeam,
            'examAttempt' => $attempt,
        ]), [
            'answers' => [
                [
                    'question_id' => $attempt->metadata['selected_question_ids'][0],
                    'answer' => ['A'],
                ],
            ],
        ])
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $examOverrides
 * @param  array<string, mixed>  $enrollmentOverrides
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Exam, 4: Collection<int, Question>}
 */
function studentExamAttemptFixture(array $examOverrides = [], array $enrollmentOverrides = []): array
{
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()->for($team)->for($user)->create();
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Course']);
    $coursePackage = CoursePackage::factory()
        ->for($team)
        ->for($course)
        ->create([
            'max_exam_attempts' => (int) ($examOverrides['max_attempts'] ?? 3),
        ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($coursePackage, 'coursePackage')
        ->create(array_merge([
            'status' => Enrollment::STATUS_ACTIVE,
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $enrollmentOverrides));
    $exam = Exam::factory()
        ->for($team)
        ->for($course)
        ->create(array_merge([
            'title' => 'Kahramaa Mock',
            'is_active' => true,
            'duration_minutes' => 30,
            'pass_mark' => 70,
        ], $examOverrides));
    $questions = collect([
        ['topic' => 'Safety', 'correct_answer' => ['A'], 'explanation' => 'Use approved isolation sequence.'],
        ['topic' => 'Load', 'correct_answer' => ['B'], 'explanation' => 'Use the connected load.'],
        ['topic' => 'Drawings', 'correct_answer' => ['C'], 'explanation' => 'Read the single line diagram.'],
    ])->map(fn (array $attributes): Question => Question::factory()
        ->for($team)
        ->for($course)
        ->create([
            ...$attributes,
            'is_active' => true,
        ]));

    return [$user, $team, $enrollment, $exam, $questions];
}
