<?php

use App\Actions\PowerX\StartExamAttempt;
use App\Actions\PowerX\SubmitExamAttempt;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('starts gated exam attempts for paid active enrollments', function () {
    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create();
    $package = CoursePackage::factory()->for($team)->for($course)->create(['max_exam_attempts' => 2]);
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ]);
    $exam = Exam::factory()->for($team)->for($course)->create(['max_attempts' => 3, 'is_active' => true]);
    Question::factory()->for($team)->for($course)->count(3)->create(['is_active' => true]);

    $firstAttempt = app(StartExamAttempt::class)->handle($exam, $enrollment);
    $secondAttempt = app(StartExamAttempt::class)->handle($exam, $enrollment);

    expect($firstAttempt->attempt_number)->toBe(1)
        ->and($secondAttempt->attempt_number)->toBe(2)
        ->and($secondAttempt->studentProfile->is($profile))->toBeTrue();

    app(StartExamAttempt::class)->handle($exam, $enrollment);
})->throws(ValidationException::class);

it('rejects exam attempts until enrollment is active and paid', function () {
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()
        ->for($course)
        ->create(['status' => 'pending', 'payment_status' => 'pending']);
    $exam = Exam::factory()->for($course)->create(['is_active' => true]);
    Question::factory()->for($course)->create(['is_active' => true]);

    app(StartExamAttempt::class)->handle($exam, $enrollment);
})->throws(ValidationException::class);

it('scores submitted exam answers and stores pass or fail result', function () {
    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ]);
    $exam = Exam::factory()->for($team)->for($course)->create([
        'pass_mark' => 70,
        'duration_minutes' => 60,
        'is_active' => true,
    ]);
    $questionOne = Question::factory()->for($team)->for($course)->create([
        'correct_answer' => ['A'],
        'is_active' => true,
    ]);
    $questionTwo = Question::factory()->for($team)->for($course)->create([
        'correct_answer' => ['B', 'C'],
        'is_active' => true,
    ]);

    $attempt = app(StartExamAttempt::class)->handle($exam, $enrollment);
    $submittedAttempt = app(SubmitExamAttempt::class)->handle($attempt, [
        ['question_id' => $questionOne->id, 'answer' => 'A'],
        ['question_id' => $questionTwo->id, 'answer' => ['C', 'B']],
    ]);

    expect($submittedAttempt->result)->toBe('passed')
        ->and($submittedAttempt->score)->toBe('100.00')
        ->and($submittedAttempt->answers)->toHaveCount(2)
        ->and($submittedAttempt->metadata['correct_answers'])->toBe(2);
});

it('rejects submitted answers from another course', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $enrollment = Enrollment::factory()
        ->for($course)
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
    $exam = Exam::factory()->for($course)->create(['is_active' => true]);
    $otherQuestion = Question::factory()->for($otherCourse)->create(['is_active' => true]);
    $attempt = app(StartExamAttempt::class)->handle($exam, $enrollment);

    app(SubmitExamAttempt::class)->handle($attempt, [
        ['question_id' => $otherQuestion->id, 'answer' => 'A'],
    ]);
})->throws(ValidationException::class);
