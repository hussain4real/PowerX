<?php

use App\Actions\PowerX\IssueCertificate;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('issues an eligible certificate and verifies it publicly', function () {
    $this->withoutVite();

    [$enrollment, $approver] = eligibleCertificateEnrollment();

    $certificate = app(IssueCertificate::class)->handle($enrollment, $approver);
    $sameCertificate = app(IssueCertificate::class)->handle($enrollment, $approver);

    expect($certificate->status)->toBe('issued')
        ->and($certificate->result)->toBe('passed')
        ->and($certificate->approvedBy->is($approver))->toBeTrue()
        ->and($sameCertificate->is($certificate))->toBeTrue()
        ->and(Certificate::count())->toBe(1)
        ->and(AuditEvent::where('action', 'certificate.issued')->count())->toBe(1);

    $auditEvent = AuditEvent::where('action', 'certificate.issued')->firstOrFail();

    expect($auditEvent->team->is($enrollment->team))->toBeTrue()
        ->and($auditEvent->actor->is($approver))->toBeTrue()
        ->and($auditEvent->subject->is($certificate))->toBeTrue()
        ->and($auditEvent->after['certificate_number'])->toBe($certificate->certificate_number)
        ->and($auditEvent->metadata['enrollment_id'])->toBe($enrollment->id);

    $this->get(route('certificates.verify', ['token' => $certificate->verification_token]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Certificates/Verify')
            ->where('certificate.number', $certificate->certificate_number)
            ->where('certificate.student.name', $enrollment->studentProfile->full_name)
            ->where('certificate.course.title', $enrollment->course->title)
            ->missing('certificate.student.email')
            ->missing('certificate.student.mobile')
            ->missing('certificate.metadata'));
});

it('blocks certificates until lessons are completed', function () {
    [$enrollment, $approver] = eligibleCertificateEnrollment(includeLessonProgress: false);

    app(IssueCertificate::class)->handle($enrollment, $approver);
})->throws(ValidationException::class);

it('blocks certificates when practical assessment failed', function () {
    [$enrollment, $approver, $session] = eligibleCertificateEnrollment();

    AttendanceRecord::factory()
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->for($approver, 'markedBy')
        ->create(['practical_outcome' => 'failed']);

    app(IssueCertificate::class)->handle($enrollment, $approver);
})->throws(ValidationException::class);

it('requires passed practical assessment when package configuration demands it', function () {
    [$enrollment, $approver, $session] = eligibleCertificateEnrollment();
    $enrollment->coursePackage->update(['requires_practical_pass_for_certificate' => true]);

    expect(fn () => app(IssueCertificate::class)->handle($enrollment->fresh(), $approver))
        ->toThrow(ValidationException::class);

    AttendanceRecord::factory()
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->for($approver, 'markedBy')
        ->create(['status' => 'present', 'practical_outcome' => 'passed']);

    $certificate = app(IssueCertificate::class)->handle($enrollment->fresh(), $approver);

    expect($certificate->status)->toBe('issued')
        ->and($certificate->metadata['eligibility']['requirements']['requires_practical_pass'])->toBeTrue()
        ->and($certificate->metadata['eligibility']['passed_practical'])->toBeTrue();
});

it('applies configurable lesson exam and attendance certificate requirements', function () {
    [$enrollment, $approver, $session] = eligibleCertificateEnrollment(includeLessonProgress: false);

    $enrollment->coursePackage->update([
        'requires_lesson_completion_for_certificate' => false,
        'requires_exam_pass_for_certificate' => false,
        'requires_attendance_for_certificate' => true,
    ]);
    $enrollment->examAttempts()->delete();

    expect(fn () => app(IssueCertificate::class)->handle($enrollment->fresh(), $approver))
        ->toThrow(ValidationException::class);

    AttendanceRecord::factory()
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->for($approver, 'markedBy')
        ->create(['status' => 'present', 'practical_outcome' => null]);

    $certificate = app(IssueCertificate::class)->handle($enrollment->fresh(), $approver);

    expect($certificate->status)->toBe('issued')
        ->and($certificate->metadata['eligibility']['requirements']['requires_lesson_completion'])->toBeFalse()
        ->and($certificate->metadata['eligibility']['requirements']['requires_exam_pass'])->toBeFalse()
        ->and($certificate->metadata['eligibility']['requirements']['requires_attendance'])->toBeTrue()
        ->and($certificate->metadata['eligibility']['has_attendance'])->toBeTrue();
});

it('does not verify draft certificates publicly', function () {
    $certificate = Certificate::factory()->create(['status' => 'draft', 'issued_at' => null]);

    $this->get(route('certificates.verify', ['token' => $certificate->verification_token]))
        ->assertNotFound();
});

/**
 * @return array{0: Enrollment, 1: User, 2: TrainingSession}
 */
function eligibleCertificateEnrollment(bool $includeLessonProgress = true): array
{
    $team = Team::factory()->create();
    $approver = User::factory()->create();
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Preparation']);
    $package = CoursePackage::factory()
        ->for($team)
        ->for($course)
        ->create(['includes_certificate' => true]);
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subMonth(),
            'access_expires_at' => now()->addMonth(),
        ]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()->for($module, 'courseModule')->create(['is_active' => true]);

    if ($includeLessonProgress) {
        LessonProgress::factory()
            ->for($enrollment)
            ->for($lesson)
            ->create([
                'progress_percentage' => 100,
                'completed_at' => now(),
            ]);
    }

    $exam = Exam::factory()->for($team)->for($course)->create(['is_active' => true]);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create(['result' => 'passed', 'score' => 88]);

    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($approver, 'instructor')
        ->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();

    return [$enrollment, $approver, $session];
}
