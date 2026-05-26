<?php

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Company;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PaymentTransaction;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates every PowerX domain factory with sensible defaults', function () {
    $records = [
        Company::factory()->create(),
        StudentProfile::factory()->create(),
        Course::factory()->create(),
        CoursePackage::factory()->create(),
        CourseModule::factory()->create(),
        Lesson::factory()->create(),
        Lead::factory()->create(),
        Enrollment::factory()->create(),
        TrainingBatch::factory()->create(),
        TrainingSession::factory()->create(),
        Invoice::factory()->create(),
        PaymentTransaction::factory()->create(),
        Question::factory()->create(),
        Exam::factory()->create(),
        ExamAttempt::factory()->create(),
        Certificate::factory()->create(),
        Communication::factory()->create(),
        AttendanceRecord::factory()->create(),
        LessonProgress::factory()->create(),
    ];

    foreach ($records as $record) {
        $this->assertModelExists($record);
    }
});

it('persists the core PowerX training workflow relationships', function () {
    $team = Team::factory()->create(['name' => 'PowerX Operations']);
    $student = User::factory()->create();
    $instructor = User::factory()->create();
    $manager = User::factory()->create();
    $company = Company::factory()->for($team)->create();
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($student, 'user')
        ->for($company)
        ->create();
    $course = Course::factory()->for($team)->create([
        'title' => 'Kahramaa Electrical Safety Preparation',
        'slug' => 'kahramaa-electrical-safety-preparation',
    ]);
    $package = CoursePackage::factory()->for($team)->for($course)->create();
    $module = CourseModule::factory()->for($course)->create(['sort_order' => 1]);
    $lesson = Lesson::factory()->for($module, 'courseModule')->create(['sort_order' => 1]);
    $lead = Lead::factory()
        ->for($team)
        ->for($manager, 'owner')
        ->for($company)
        ->for($course)
        ->create(['status' => 'qualified']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($company)
        ->for($course)
        ->for($package, 'coursePackage')
        ->for($manager, 'approvedBy')
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($instructor, 'instructor')
        ->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();
    $invoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->create(['status' => 'issued', 'total' => 1500]);
    $payment = PaymentTransaction::factory()
        ->for($team)
        ->for($enrollment)
        ->for($invoice)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($manager, 'approvedBy')
        ->create(['amount' => 1500, 'status' => 'approved']);
    $question = Question::factory()->for($team)->for($course)->create();
    $exam = Exam::factory()->for($team)->for($course)->create(['pass_mark' => 70]);
    $attempt = ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create(['result' => 'passed', 'score' => 86.5]);
    $certificate = Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($manager, 'approvedBy')
        ->create(['status' => 'issued']);
    $communication = Communication::factory()
        ->for($team)
        ->for($lead)
        ->for($profile, 'studentProfile')
        ->for($company)
        ->for($student, 'user')
        ->create(['channel' => 'email']);

    expect($team->courses()->first()->is($course))->toBeTrue()
        ->and($course->modules()->first()->is($module))->toBeTrue()
        ->and($module->lessons()->first()->is($lesson))->toBeTrue()
        ->and($company->studentProfiles()->first()->is($profile))->toBeTrue()
        ->and($lead->owner->is($manager))->toBeTrue()
        ->and($enrollment->studentProfile->is($profile))->toBeTrue()
        ->and($enrollment->coursePackage->is($package))->toBeTrue()
        ->and($batch->sessions()->first()->is($session))->toBeTrue()
        ->and($invoice->paymentTransactions()->first()->is($payment))->toBeTrue()
        ->and($question->course->is($course))->toBeTrue()
        ->and($exam->attempts()->first()->is($attempt))->toBeTrue()
        ->and($certificate->verification_token)->not->toBeEmpty()
        ->and($communication->studentProfile->is($profile))->toBeTrue();
});

it('tracks internal course and lesson content retirement without hiding active records', function () {
    $team = Team::factory()->create();
    $replacementCourse = Course::factory()->for($team)->create(['title' => 'Updated Electrical Prep']);
    $course = Course::factory()->for($team)->create([
        'content_revision' => 2,
        'content_retired_at' => now()->subDay(),
        'replacement_course_id' => $replacementCourse->id,
        'content_retirement_note' => 'Preparing updated course content.',
    ]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $replacementLesson = Lesson::factory()->for($module, 'courseModule')->create(['title' => 'Updated safety overview']);
    $lesson = Lesson::factory()->for($module, 'courseModule')->create([
        'content_revision' => 3,
        'content_retired_at' => now()->subDay(),
        'replacement_lesson_id' => $replacementLesson->id,
        'content_retirement_note' => 'Preparing updated lesson content.',
        'is_active' => true,
    ]);

    expect(Course::contentRetired()->whereKey($course)->exists())->toBeTrue()
        ->and($course->fresh()->isContentRetired())->toBeTrue()
        ->and($course->fresh()->replacementCourse->is($replacementCourse))->toBeTrue()
        ->and($replacementCourse->replacedCourses()->whereKey($course)->exists())->toBeTrue()
        ->and(Lesson::active()->whereKey($lesson)->exists())->toBeTrue()
        ->and(Lesson::contentRetired()->whereKey($lesson)->exists())->toBeTrue()
        ->and($lesson->fresh()->isContentRetired())->toBeTrue()
        ->and($lesson->fresh()->replacementLesson->is($replacementLesson))->toBeTrue()
        ->and($replacementLesson->replacedLessons()->whereKey($lesson)->exists())->toBeTrue();
});
