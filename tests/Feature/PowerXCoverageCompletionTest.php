<?php

use App\Actions\PowerX\CreateLeadInquiry;
use App\Actions\PowerX\IssueCertificate;
use App\Actions\PowerX\IssueInvoice;
use App\Actions\PowerX\RecordManualPayment;
use App\Actions\PowerX\RegisterCourseInterest;
use App\Actions\PowerX\StartExamAttempt;
use App\Actions\PowerX\SubmitExamAttempt;
use App\Enums\PowerXPermission;
use App\Enums\PowerXRole;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('covers PowerX enum labels and role permission maps', function () {
    expect(collect(PowerXPermission::cases())->map->label()->all())->toBe([
        'Access admin panel',
        'Manage users',
        'Manage roles and permissions',
        'Manage CRM leads',
        'Manage registrations',
        'Manage courses',
        'Manage learning content',
        'Manage batches',
        'Manage attendance',
        'Manage exams',
        'Manage payments',
        'Manage certificates',
        'View reports',
        'Manage communications',
        'Manage support',
        'Manage settings',
    ]);

    expect(collect(PowerXRole::cases())->map->label()->all())->toBe([
        'Management',
        'Admin',
        'Sales',
        'Finance',
        'Instructor',
        'Student',
        'Corporate',
        'Support',
    ])
        ->and(PowerXRole::Management->permissions())->toBe(PowerXPermission::cases())
        ->and(PowerXRole::Admin->permissions())->toBe(PowerXPermission::cases())
        ->and(PowerXRole::Sales->permissions())->toContain(PowerXPermission::ManageLeads)
        ->and(PowerXRole::Finance->permissions())->toContain(PowerXPermission::ManagePayments)
        ->and(PowerXRole::Instructor->permissions())->toContain(PowerXPermission::ManageAttendance)
        ->and(PowerXRole::Support->permissions())->toContain(PowerXPermission::ManageSupport)
        ->and(PowerXRole::Student->permissions())->toBe([])
        ->and(PowerXRole::Corporate->permissions())->toBe([]);
});

it('covers PowerX model relationship and media registration methods', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $company = Company::factory()->for($team)->create();
    $studentProfile = StudentProfile::factory()
        ->for($team)
        ->for($user)
        ->for($company)
        ->create();
    $course = Course::factory()->for($team)->create([
        'status' => 'published',
        'published_at' => now()->addDay(),
    ]);
    $coursePackage = CoursePackage::factory()->for($team)->for($course)->create(['is_active' => true]);
    $courseModule = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()->for($courseModule, 'courseModule')->create(['is_active' => true]);
    $lead = Lead::factory()->for($team)->for($company)->for($course)->for($user, 'owner')->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($studentProfile, 'studentProfile')
        ->for($company)
        ->for($course)
        ->for($coursePackage, 'coursePackage')
        ->for($user, 'approvedBy')
        ->create();
    $batch = TrainingBatch::factory()->for($team)->for($course)->for($user, 'instructor')->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();
    $invoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($company)
        ->for($studentProfile, 'studentProfile')
        ->create(['status' => 'issued']);
    $payment = PaymentTransaction::factory()
        ->for($team)
        ->for($enrollment)
        ->for($invoice)
        ->for($company)
        ->for($studentProfile, 'studentProfile')
        ->for($user, 'approvedBy')
        ->create(['status' => 'approved']);
    $question = Question::factory()->for($team)->for($course)->create(['is_active' => true]);
    $exam = Exam::factory()->for($team)->for($course)->create(['is_active' => true]);
    $attempt = ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($studentProfile, 'studentProfile')
        ->create();
    $certificate = Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($studentProfile, 'studentProfile')
        ->for($course)
        ->for($user, 'approvedBy')
        ->create();
    $communication = Communication::factory()
        ->for($team)
        ->for($lead)
        ->for($studentProfile, 'studentProfile')
        ->for($company)
        ->for($user)
        ->create();
    $attendance = AttendanceRecord::factory()
        ->for($team)
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->for($user, 'markedBy')
        ->for($user, 'assessedBy')
        ->create(['status' => 'late']);
    $progress = LessonProgress::factory()->for($enrollment)->for($lesson)->create(['completed_at' => now()]);
    $auditEvent = AuditEvent::factory()
        ->for($team)
        ->for($user, 'actor')
        ->create([
            'subject_type' => $payment->getMorphClass(),
            'subject_id' => $payment->id,
        ]);

    $relations = [
        $team->auditEvents(),
        $team->companies(),
        $team->studentProfiles(),
        $team->courses(),
        $team->coursePackages(),
        $team->leads(),
        $team->enrollments(),
        $team->trainingBatches(),
        $team->invoices(),
        $team->paymentTransactions(),
        $team->questions(),
        $team->exams(),
        $team->examAttempts(),
        $team->certificates(),
        $team->communications(),
        $team->attendanceRecords(),
        $company->team(),
        $company->studentProfiles(),
        $company->leads(),
        $company->enrollments(),
        $company->invoices(),
        $company->paymentTransactions(),
        $company->communications(),
        $studentProfile->team(),
        $studentProfile->user(),
        $studentProfile->company(),
        $studentProfile->enrollments(),
        $studentProfile->invoices(),
        $studentProfile->paymentTransactions(),
        $studentProfile->examAttempts(),
        $studentProfile->certificates(),
        $studentProfile->communications(),
        $course->team(),
        $course->packages(),
        $course->modules(),
        $course->leads(),
        $course->enrollments(),
        $course->trainingBatches(),
        $course->questions(),
        $course->exams(),
        $course->certificates(),
        $coursePackage->enrollments(),
        $lesson->progressRecords(),
        $lead->communications(),
        $enrollment->paymentTransactions(),
        $enrollment->examAttempts(),
        $enrollment->certificates(),
        $enrollment->attendanceRecords(),
        $enrollment->lessonProgress(),
        $invoice->paymentTransactions(),
        $payment->approvedBy(),
        $auditEvent->team(),
        $auditEvent->actor(),
        $auditEvent->subject(),
        $certificate->approvedBy(),
        $user->studentProfile(),
        $user->assignedLeads(),
        $user->approvedEnrollments(),
        $user->instructedBatches(),
        $user->markedAttendanceRecords(),
        $user->assessedAttendanceRecords(),
    ];

    foreach ($relations as $relation) {
        expect($relation)->toBeObject();
    }

    $course->registerMediaCollections();
    $studentProfile->registerMediaCollections();
    $lesson->registerMediaCollections();
    $invoice->registerMediaCollections();
    $certificate->registerMediaCollections();

    expect(Course::published()->whereKey($course)->doesntExist())->toBeTrue()
        ->and($course->isPublished())->toBeFalse()
        ->and($course->getRouteKeyName())->toBe('slug')
        ->and(CoursePackage::active()->whereKey($coursePackage)->exists())->toBeTrue()
        ->and(Lesson::active()->whereKey($lesson)->exists())->toBeTrue()
        ->and(Invoice::issued()->whereKey($invoice)->exists())->toBeTrue()
        ->and(PaymentTransaction::approved()->whereKey($payment)->exists())->toBeTrue()
        ->and(AttendanceRecord::present()->whereKey($attendance)->exists())->toBeTrue()
        ->and(Question::active()->whereKey($question)->exists())->toBeTrue()
        ->and(Exam::active()->whereKey($exam)->exists())->toBeTrue()
        ->and(LessonProgress::completed()->whereKey($progress)->exists())->toBeTrue()
        ->and($attempt->exists)->toBeTrue()
        ->and($communication->exists)->toBeTrue();
});

it('covers public inquiry and registration defaults', function () {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);

    $lead = app(CreateLeadInquiry::class)->handle([
        'name' => 'No Company Prospect',
        'email' => 'prospect@example.com',
        'course_interest' => 'Electrical safety',
    ]);
    $enrollment = app(RegisterCourseInterest::class)->handle($course, [
        'full_name' => 'No Company Student',
        'email' => 'student@example.com',
        'mobile' => '+97450000001',
    ]);

    expect($lead->company)->toBeNull()
        ->and($lead->source)->toBe('website')
        ->and($lead->course_interest)->toBe('Electrical safety')
        ->and($enrollment->coursePackage)->toBeNull()
        ->and($enrollment->company)->toBeNull()
        ->and($enrollment->metadata['requested_package'])->toBeNull();
});

it('covers manual invoice number generation and payment proof media', function () {
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->for($course)->create();
    $invoice = app(IssueInvoice::class)->handle($enrollment, [
        'subtotal' => 500,
    ]);
    $payment = app(RecordManualPayment::class)->handle($invoice, [
        'amount' => 500,
    ], UploadedFile::fake()->create('payment-proof.pdf', 12, 'application/pdf'));

    expect($invoice->number)->toStartWith('PX-INV-')
        ->and($invoice->metadata['line_items'][0]['description'])->toBe($course->title)
        ->and($payment->getMedia('payment-proofs'))->toHaveCount(1);
});

it('covers exam start eligibility failures', function (string $state) {
    [$exam, $enrollment] = coverageExamEnrollment();

    match ($state) {
        'inactive exam' => $exam->update(['is_active' => false]),
        'wrong course' => $exam->update(['course_id' => Course::factory()->create()->id]),
        'future access' => $enrollment->update(['access_starts_at' => now()->addDay()]),
        'expired access' => $enrollment->update(['access_expires_at' => now()->subDay()]),
    };

    app(StartExamAttempt::class)->handle($exam->fresh(), $enrollment->fresh());
})->with([
    'inactive exam',
    'wrong course',
    'future access',
    'expired access',
])->throws(ValidationException::class);

it('covers exam submission validation and failed scoring branches', function () {
    [$exam, $enrollment, $question] = coverageExamEnrollmentWithQuestion();

    $submittedAttempt = ExamAttempt::factory()
        ->for($exam)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create(['submitted_at' => now(), 'result' => 'passed']);
    expect(fn () => app(SubmitExamAttempt::class)->handle($submittedAttempt, [
        ['question_id' => $question->id, 'answer' => 'A'],
    ]))->toThrow(ValidationException::class);

    $expiredAttempt = ExamAttempt::factory()
        ->for($exam)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'started_at' => now()->subMinutes($exam->duration_minutes + 6),
            'submitted_at' => null,
        ]);
    expect(fn () => app(SubmitExamAttempt::class)->handle($expiredAttempt, [
        ['question_id' => $question->id, 'answer' => 'A'],
    ]))->toThrow(ValidationException::class);

    $emptyAttempt = app(StartExamAttempt::class)->handle($exam, $enrollment);
    expect(fn () => app(SubmitExamAttempt::class)->handle($emptyAttempt, []))
        ->toThrow(ValidationException::class);

    $failedAttempt = app(StartExamAttempt::class)->handle($exam, $enrollment);
    $result = app(SubmitExamAttempt::class)->handle($failedAttempt, [
        ['question_id' => $question->id, 'answer' => 'B'],
    ]);

    expect($result->result)->toBe('failed')
        ->and($result->score)->toBe('0.00');
});

it('covers certificate eligibility failures', function (string $state) {
    [$enrollment] = coverageCertificateEnrollment();

    match ($state) {
        'unpaid' => $enrollment->update(['payment_status' => 'pending']),
        'package excludes certificate' => $enrollment->coursePackage->update(['includes_certificate' => false]),
        'future access' => $enrollment->update(['access_starts_at' => now()->addDay()]),
        'expired access' => $enrollment->update(['access_expires_at' => now()->subDay()]),
        'no passed exam' => $enrollment->examAttempts()->update(['result' => 'failed']),
    };

    app(IssueCertificate::class)->handle($enrollment->fresh());
})->with([
    'unpaid',
    'package excludes certificate',
    'future access',
    'expired access',
    'no passed exam',
])->throws(ValidationException::class);

/**
 * @return array{0: Exam, 1: Enrollment}
 */
function coverageExamEnrollment(): array
{
    $course = Course::factory()->create();
    $coursePackage = CoursePackage::factory()->for($course)->create(['max_exam_attempts' => 10]);
    $profile = StudentProfile::factory()->create();
    $enrollment = Enrollment::factory()
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($coursePackage, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addDay(),
        ]);
    $exam = Exam::factory()->for($course)->create([
        'is_active' => true,
        'duration_minutes' => 30,
        'max_attempts' => 10,
    ]);

    return [$exam, $enrollment];
}

/**
 * @return array{0: Exam, 1: Enrollment, 2: Question}
 */
function coverageExamEnrollmentWithQuestion(): array
{
    [$exam, $enrollment] = coverageExamEnrollment();
    $question = Question::factory()->for($exam->course)->create([
        'correct_answer' => ['A'],
        'is_active' => true,
    ]);

    return [$exam, $enrollment, $question];
}

/**
 * @return array{0: Enrollment}
 */
function coverageCertificateEnrollment(): array
{
    $course = Course::factory()->create();
    $package = CoursePackage::factory()->for($course)->create(['includes_certificate' => true]);
    $profile = StudentProfile::factory()->create();
    $enrollment = Enrollment::factory()
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addDay(),
        ]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()->for($module, 'courseModule')->create(['is_active' => true]);
    LessonProgress::factory()->for($enrollment)->for($lesson)->create([
        'progress_percentage' => 100,
        'completed_at' => now(),
    ]);
    $exam = Exam::factory()->for($course)->create(['is_active' => true]);
    ExamAttempt::factory()
        ->for($exam)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create(['result' => 'passed']);

    return [$enrollment];
}
