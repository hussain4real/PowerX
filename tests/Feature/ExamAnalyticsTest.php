<?php

use App\Actions\PowerX\BuildExamAnalytics;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;

test('exam analytics summarize course exam student delivery question and weak topic performance', function (): void {
    [$team, $instructor, $course, $batch, $exam, $questions] = powerxExamAnalyticsFixtures();

    $analytics = app(BuildExamAnalytics::class)->handle($team);

    expect($analytics['summary'])->toMatchArray([
        'total_attempts' => 3,
        'submitted_attempts' => 3,
        'passed_attempts' => 1,
        'failed_attempts' => 2,
        'pass_rate' => 33.3,
        'average_score' => 66.7,
        'average_duration_minutes' => 40.0,
        'weak_topic_count' => 4,
        'top_weak_topic' => 'Switchgear safety',
        'top_weak_topic_occurrences' => 2,
    ])
        ->and($analytics['courses'][0])->toMatchArray([
            'courseId' => $course->id,
            'courseTitle' => 'Kahramaa Exam Preparation',
            'attempts' => 3,
            'passed' => 1,
            'failed' => 2,
            'passRate' => 33.3,
            'averageScore' => 66.7,
        ])
        ->and($analytics['exams'][0]['examTitle'])->toBe($exam->title)
        ->and($analytics['students'][0]['studentName'])->toBe('Aisha Candidate')
        ->and($analytics['students'][0]['bestScore'])->toBe(90.0)
        ->and($analytics['batches'][0])->toMatchArray([
            'batchId' => $batch->id,
            'batchName' => 'PX-ANALYTICS-01',
            'attempts' => 2,
            'passed' => 1,
            'passRate' => 50.0,
        ])
        ->and($analytics['batches'][1]['batchName'])->toBe('Unassigned batch')
        ->and($analytics['instructors'][0])->toMatchArray([
            'instructorId' => $instructor->id,
            'instructorName' => 'Instructor Noor',
            'attempts' => 2,
            'passRate' => 50.0,
        ])
        ->and($analytics['instructors'][1]['instructorName'])->toBe('Unassigned instructor')
        ->and(collect($analytics['questions'])->firstWhere('questionId', $questions['switchgear']->id))->toMatchArray([
            'topic' => 'Switchgear safety',
            'attempts' => 2,
            'correctAnswers' => 1,
            'incorrectAnswers' => 1,
            'accuracyRate' => 50.0,
        ])
        ->and(collect($analytics['questions'])->firstWhere('questionId', $questions['earthing']->id))->toMatchArray([
            'topic' => 'Earthing',
            'attempts' => 1,
            'correctAnswers' => 1,
            'incorrectAnswers' => 0,
            'accuracyRate' => 100.0,
        ])
        ->and($analytics['weakTopics'][0])->toMatchArray([
            'topic' => 'Switchgear safety',
            'occurrences' => 2,
        ]);
});

test('exam analytics return empty structures for teams without submitted attempts', function (): void {
    $team = Team::factory()->create();

    $analytics = app(BuildExamAnalytics::class)->handle($team);

    expect($analytics['summary'])->toMatchArray([
        'total_attempts' => 0,
        'submitted_attempts' => 0,
        'passed_attempts' => 0,
        'failed_attempts' => 0,
        'pass_rate' => 0.0,
        'average_score' => 0.0,
        'average_duration_minutes' => 0.0,
        'weak_topic_count' => 0,
        'top_weak_topic' => null,
        'top_weak_topic_occurrences' => 0,
    ])
        ->and($analytics['courses'])->toBe([])
        ->and($analytics['exams'])->toBe([])
        ->and($analytics['students'])->toBe([])
        ->and($analytics['batches'])->toBe([])
        ->and($analytics['instructors'])->toBe([])
        ->and($analytics['questions'])->toBe([])
        ->and($analytics['weakTopics'])->toBe([]);
});

/**
 * @return array{0: Team, 1: User, 2: Course, 3: TrainingBatch, 4: Exam, 5: array<string, Question>}
 */
function powerxExamAnalyticsFixtures(): array
{
    $team = Team::factory()->create();
    $instructor = User::factory()->create(['name' => 'Instructor Noor']);
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Preparation']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($instructor, 'instructor')
        ->create(['name' => 'PX-ANALYTICS-01']);
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();
    $exam = Exam::factory()->for($team)->for($course)->create(['title' => 'Final Mock Exam']);
    $questions = [
        'switchgear' => Question::factory()->for($team)->for($course)->create([
            'topic' => 'Switchgear safety',
            'difficulty' => 'advanced',
            'correct_answer' => ['A'],
        ]),
        'load' => Question::factory()->for($team)->for($course)->create([
            'topic' => 'Load calculation',
            'difficulty' => 'intermediate',
            'correct_answer' => ['B'],
        ]),
        'drawings' => Question::factory()->for($team)->for($course)->create([
            'topic' => 'Drawings',
            'difficulty' => 'standard',
            'correct_answer' => ['C'],
        ]),
        'earthing' => Question::factory()->for($team)->for($course)->create([
            'topic' => 'Earthing',
            'difficulty' => 'standard',
            'correct_answer' => ['D'],
        ]),
    ];

    $aishaProfile = StudentProfile::factory()->for($team)->create(['full_name' => 'Aisha Candidate']);
    $bilalProfile = StudentProfile::factory()->for($team)->create(['full_name' => 'Bilal Candidate']);
    $unassignedProfile = StudentProfile::factory()->for($team)->create(['full_name' => 'Unassigned Candidate']);
    $aishaEnrollment = Enrollment::factory()->for($team)->for($course)->for($aishaProfile, 'studentProfile')->create();
    $bilalEnrollment = Enrollment::factory()->for($team)->for($course)->for($bilalProfile, 'studentProfile')->create();
    $unassignedEnrollment = Enrollment::factory()->for($team)->for($course)->for($unassignedProfile, 'studentProfile')->create();

    AttendanceRecord::factory()->for($team)->for($session, 'trainingSession')->for($aishaEnrollment)->create();
    AttendanceRecord::factory()->for($team)->for($session, 'trainingSession')->for($bilalEnrollment)->create();

    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($aishaEnrollment)
        ->for($aishaProfile, 'studentProfile')
        ->create([
            'attempt_number' => 1,
            'result' => 'passed',
            'score' => 90,
            'duration_seconds' => 1800,
            'answers' => [
                ['question_id' => $questions['switchgear']->id, 'answer' => ['A']],
                ['question_id' => $questions['load']->id, 'answer' => ['A']],
                ['question_id' => $questions['drawings']->id, 'answer' => ['C'], 'is_correct' => true],
                ['question_id' => $questions['earthing']->id, 'answer' => ['D']],
            ],
            'metadata' => ['weak_topic' => 'Switchgear safety'],
        ]);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($bilalEnrollment)
        ->for($bilalProfile, 'studentProfile')
        ->create([
            'attempt_number' => 1,
            'result' => 'failed',
            'score' => 60,
            'duration_seconds' => 2400,
            'answers' => [
                ['question_id' => $questions['switchgear']->id, 'answer' => ['B']],
                ['question_id' => $questions['drawings']->id, 'answer' => ['D'], 'is_correct' => false],
                ['question_id' => 999999, 'answer' => ['A']],
            ],
            'metadata' => [],
        ]);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($unassignedEnrollment)
        ->for($unassignedProfile, 'studentProfile')
        ->create([
            'attempt_number' => 1,
            'result' => 'failed',
            'score' => 50,
            'duration_seconds' => 3000,
            'answers' => [],
            'metadata' => ['weak_topic' => 'Transformer protection'],
        ]);
    ExamAttempt::factory()->create(['score' => 100, 'result' => 'passed', 'submitted_at' => now()]);

    return [$team, $instructor, $course, $batch, $exam, $questions];
}
