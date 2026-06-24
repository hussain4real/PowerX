<?php

use App\Enums\PowerXRole;
use App\Filament\PowerXResource;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\AuditEvents\AuditEventResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Communications\CommunicationResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;
use App\Filament\Resources\CoursePackages\CoursePackageResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\ExamAttempts\ExamAttemptResource;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\LessonProgress\LessonProgressResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\StudentProfiles\StudentProfileResource;
use App\Filament\Resources\TrainingBatches\TrainingBatchResource;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
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
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('powerx filament resources are grouped for operations navigation', function () {
    expect(LeadResource::getNavigationGroup())->toBe('Sales & CRM')
        ->and(AuditEventResource::getNavigationGroup())->toBe('Settings')
        ->and(CommunicationResource::getNavigationGroup())->toBe('Sales & CRM')
        ->and(CompanyResource::getNavigationGroup())->toBe('Admissions')
        ->and(StudentProfileResource::getNavigationGroup())->toBe('Admissions')
        ->and(EnrollmentResource::getNavigationGroup())->toBe('Admissions')
        ->and(CourseResource::getNavigationGroup())->toBe('Courses & LMS')
        ->and(CoursePackageResource::getNavigationGroup())->toBe('Courses & LMS')
        ->and(CourseModuleResource::getNavigationGroup())->toBe('Courses & LMS')
        ->and(LessonResource::getNavigationGroup())->toBe('Courses & LMS')
        ->and(LessonProgressResource::getNavigationGroup())->toBe('Courses & LMS')
        ->and(TrainingBatchResource::getNavigationGroup())->toBe('Training Operations')
        ->and(TrainingSessionResource::getNavigationGroup())->toBe('Training Operations')
        ->and(AttendanceRecordResource::getNavigationGroup())->toBe('Training Operations')
        ->and(InvoiceResource::getNavigationGroup())->toBe('Finance')
        ->and(PaymentTransactionResource::getNavigationGroup())->toBe('Finance')
        ->and(QuestionResource::getNavigationGroup())->toBe('Exams')
        ->and(ExamResource::getNavigationGroup())->toBe('Exams')
        ->and(ExamAttemptResource::getNavigationGroup())->toBe('Exams')
        ->and(CertificateResource::getNavigationGroup())->toBe('Certificates');
});

test('management can access all powerx filament resources', function () {
    actingAsPowerXRole(PowerXRole::Management);

    foreach (powerxFilamentResources() as $resource) {
        expect($resource::canViewAny())->toBeTrue($resource);
    }
});

test('student role cannot access staff operations resources', function () {
    actingAsPowerXRole(PowerXRole::Student);

    foreach (powerxFilamentResources() as $resource) {
        expect($resource::canViewAny())->toBeFalse($resource);
    }
});

test('powerx resource permissions follow operational roles', function (PowerXRole $role, array $allowedResources, array $blockedResources) {
    actingAsPowerXRole($role);

    foreach ($allowedResources as $resource) {
        expect($resource::canViewAny())->toBeTrue($resource);
    }

    foreach ($blockedResources as $resource) {
        expect($resource::canViewAny())->toBeFalse($resource);
    }
})->with([
    'sales' => [
        PowerXRole::Sales,
        [
            LeadResource::class,
            CommunicationResource::class,
            CompanyResource::class,
            StudentProfileResource::class,
            EnrollmentResource::class,
        ],
        [
            InvoiceResource::class,
            PaymentTransactionResource::class,
            CertificateResource::class,
            AuditEventResource::class,
            ExamResource::class,
        ],
    ],
    'finance' => [
        PowerXRole::Finance,
        [
            CompanyResource::class,
            StudentProfileResource::class,
            EnrollmentResource::class,
            InvoiceResource::class,
            PaymentTransactionResource::class,
        ],
        [
            LeadResource::class,
            CommunicationResource::class,
            CourseResource::class,
            CertificateResource::class,
            AuditEventResource::class,
        ],
    ],
    'instructor' => [
        PowerXRole::Instructor,
        [
            CourseResource::class,
            CourseModuleResource::class,
            LessonResource::class,
            LessonProgressResource::class,
            TrainingBatchResource::class,
            TrainingSessionResource::class,
            AttendanceRecordResource::class,
            QuestionResource::class,
            ExamResource::class,
            ExamAttemptResource::class,
        ],
        [
            LeadResource::class,
            InvoiceResource::class,
            PaymentTransactionResource::class,
            CertificateResource::class,
            AuditEventResource::class,
        ],
    ],
]);

test('powerx resources extend the shared operations authorization base', function () {
    foreach (powerxFilamentResources() as $resource) {
        expect(is_subclass_of($resource, PowerXResource::class))->toBeTrue($resource);
    }
});

test('powerx resource authorization covers staff operations', function () {
    actingAsPowerXRole(PowerXRole::Management);

    $lead = Lead::factory()->create();
    $auditEvent = AuditEvent::factory()->create();

    expect(LeadResource::canCreate())->toBeTrue()
        ->and(LeadResource::canView($lead))->toBeTrue()
        ->and(LeadResource::canEdit($lead))->toBeTrue()
        ->and(LeadResource::canDelete($lead))->toBeTrue()
        ->and(LeadResource::canDeleteAny())->toBeTrue()
        ->and(LeadResource::canForceDelete($lead))->toBeTrue()
        ->and(LeadResource::canForceDeleteAny())->toBeTrue()
        ->and(LeadResource::canRestore($lead))->toBeTrue()
        ->and(LeadResource::canRestoreAny())->toBeTrue()
        ->and(AuditEventResource::canCreate())->toBeFalse()
        ->and(AuditEventResource::canView($auditEvent))->toBeTrue()
        ->and(AuditEventResource::canEdit($auditEvent))->toBeFalse()
        ->and(AuditEventResource::canDelete($auditEvent))->toBeFalse()
        ->and(AuditEventResource::canDeleteAny())->toBeFalse();
});

test('management can open powerx filament resource index pages', function (string $path) {
    actingAsPowerXRole(PowerXRole::Management);

    $this->get($path)->assertSuccessful();
})->with(powerxFilamentResourcePaths());

test('management can open powerx filament resource create pages', function (string $path) {
    actingAsPowerXRole(PowerXRole::Management);

    $this->get("{$path}/create")->assertSuccessful();
})->with(array_values(array_diff(powerxFilamentResourcePaths(), ['/admin/audit-events'])));

test('management can view powerx filament resource records', function (string $path) {
    actingAsPowerXRole(PowerXRole::Management);
    $record = powerxFilamentRecord($path);

    $this->get("{$path}/{$record->getRouteKey()}")->assertSuccessful();
})->with(powerxFilamentResourcePaths());

test('management can open editable powerx filament resource records', function (string $path) {
    actingAsPowerXRole(PowerXRole::Management);
    $record = powerxFilamentRecord($path);

    $this->get("{$path}/{$record->getRouteKey()}/edit")->assertSuccessful();
})->with(array_values(array_diff(powerxFilamentResourcePaths(), ['/admin/audit-events'])));

test('finance can approve pending payment transactions from filament', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole(PowerXRole::Finance->value);
    $this->actingAs($financeUser);

    $enrollment = Enrollment::factory()->create([
        'status' => 'pending',
        'payment_status' => 'pending',
        'access_starts_at' => null,
        'access_expires_at' => null,
    ]);
    $invoice = Invoice::factory()
        ->for($enrollment)
        ->create([
            'type' => 'invoice',
            'status' => 'issued',
            'subtotal' => 1200,
            'total' => 1200,
            'paid_at' => null,
        ]);
    $payment = PaymentTransaction::factory()
        ->for($enrollment)
        ->for($invoice)
        ->create([
            'status' => 'pending',
            'amount' => 1200,
            'paid_at' => null,
            'approved_by_id' => null,
            'approved_at' => null,
        ]);

    Livewire::test(ListPaymentTransactions::class)
        ->assertTableActionVisible('approve', $payment)
        ->callTableAction('approve', $payment)
        ->assertNotified('Payment approved')
        ->assertTableActionHidden('approve', $payment->fresh());

    $approvedPayment = $payment->fresh();
    $auditEvent = AuditEvent::query()
        ->where('action', 'payment.approved')
        ->whereMorphedTo('subject', $approvedPayment)
        ->firstOrFail();

    expect($approvedPayment->status)->toBe('approved')
        ->and($approvedPayment->approved_by_id)->toBe($financeUser->id)
        ->and($approvedPayment->approved_at)->not->toBeNull()
        ->and($approvedPayment->paid_at)->not->toBeNull()
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($enrollment->fresh()->status)->toBe('active')
        ->and($enrollment->fresh()->payment_status)->toBe('paid')
        ->and($auditEvent->actor_id)->toBe($financeUser->id)
        ->and($auditEvent->before['status'])->toBe('pending')
        ->and($auditEvent->after['status'])->toBe('approved');
});

test('management exam rule edits are recorded as assessment configuration audit events', function () {
    $manager = User::factory()->create();
    grantPowerXRole($manager, PowerXRole::Management);
    $this->actingAs($manager);

    $course = Course::factory()
        ->for($manager->currentTeam)
        ->create();
    $exam = Exam::factory()
        ->for($manager->currentTeam)
        ->for($course)
        ->create([
            'duration_minutes' => 60,
            'pass_mark' => 70,
            'max_attempts' => 3,
            'question_count' => 25,
            'randomize_questions' => true,
            'is_active' => true,
            'metadata' => ['show_results_immediately' => true],
        ]);

    Livewire::test(EditExam::class, ['record' => $exam->id])
        ->fillForm([
            'team_id' => $manager->current_team_id,
            'course_id' => $course->id,
            'exam_type' => $exam->exam_type,
            'title' => $exam->title,
            'duration_minutes' => 45,
            'pass_mark' => 80,
            'max_attempts' => 2,
            'question_count' => 10,
            'randomize_questions' => false,
            'is_active' => true,
            'metadata' => ['show_results_immediately' => 'false'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $auditEvent = AuditEvent::query()
        ->where('action', 'assessment.configuration_changed')
        ->whereMorphedTo('subject', $exam)
        ->firstOrFail();

    expect($auditEvent->actor_id)->toBe($manager->id)
        ->and($auditEvent->metadata['configuration_scope'])->toBe('exam_attempt_rules')
        ->and($auditEvent->metadata['changed_fields'])->toContain(
            'duration_minutes',
            'pass_mark',
            'max_attempts',
            'question_count',
            'randomize_questions',
            'metadata',
        )
        ->and($auditEvent->before['duration_minutes'])->toBe(60)
        ->and($auditEvent->after['duration_minutes'])->toBe(45)
        ->and($auditEvent->before['pass_mark'])->toBe(70)
        ->and($auditEvent->after['pass_mark'])->toBe(80);
});

function actingAsPowerXRole(PowerXRole $role): void
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    test()->actingAs($user);
}

/**
 * @return array<class-string<PowerXResource>>
 */
function powerxFilamentResources(): array
{
    return [
        AuditEventResource::class,
        LeadResource::class,
        CommunicationResource::class,
        CompanyResource::class,
        StudentProfileResource::class,
        EnrollmentResource::class,
        CourseResource::class,
        CoursePackageResource::class,
        CourseModuleResource::class,
        LessonResource::class,
        LessonProgressResource::class,
        TrainingBatchResource::class,
        TrainingSessionResource::class,
        AttendanceRecordResource::class,
        InvoiceResource::class,
        PaymentTransactionResource::class,
        QuestionResource::class,
        ExamResource::class,
        ExamAttemptResource::class,
        CertificateResource::class,
    ];
}

/**
 * @return array<string>
 */
function powerxFilamentResourcePaths(): array
{
    return [
        '/admin/audit-events',
        '/admin/leads',
        '/admin/communications',
        '/admin/companies',
        '/admin/student-profiles',
        '/admin/enrollments',
        '/admin/courses',
        '/admin/course-packages',
        '/admin/course-modules',
        '/admin/lessons',
        '/admin/lesson-progress',
        '/admin/training-batches',
        '/admin/training-sessions',
        '/admin/attendance-records',
        '/admin/invoices',
        '/admin/payment-transactions',
        '/admin/questions',
        '/admin/exams',
        '/admin/exam-attempts',
        '/admin/certificates',
    ];
}

function powerxFilamentRecord(string $path): Model
{
    return match ($path) {
        '/admin/audit-events' => AuditEvent::factory()->create(),
        '/admin/leads' => Lead::factory()->create(),
        '/admin/communications' => Communication::factory()->create(),
        '/admin/companies' => Company::factory()->create(),
        '/admin/student-profiles' => StudentProfile::factory()->create(),
        '/admin/enrollments' => Enrollment::factory()->create(),
        '/admin/courses' => Course::factory()->create(),
        '/admin/course-packages' => CoursePackage::factory()->create(),
        '/admin/course-modules' => CourseModule::factory()->create(),
        '/admin/lessons' => Lesson::factory()->create(),
        '/admin/lesson-progress' => LessonProgress::factory()->create(),
        '/admin/training-batches' => TrainingBatch::factory()->create(),
        '/admin/training-sessions' => TrainingSession::factory()->create(),
        '/admin/attendance-records' => AttendanceRecord::factory()->create(),
        '/admin/invoices' => Invoice::factory()->create(),
        '/admin/payment-transactions' => PaymentTransaction::factory()->create(),
        '/admin/questions' => Question::factory()->create(),
        '/admin/exams' => Exam::factory()->create(),
        '/admin/exam-attempts' => ExamAttempt::factory()->create(),
        '/admin/certificates' => Certificate::factory()->create(),
    };
}
