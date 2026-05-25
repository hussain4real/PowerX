<?php

use App\Actions\PowerX\ApproveManualPayment;
use App\Actions\PowerX\IssueInvoice;
use App\Actions\PowerX\RecordManualPayment;
use App\Actions\PowerX\RecordPracticalAssessment;
use App\Actions\PowerX\RecordSessionAttendance;
use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('issues invoices and approves manual payments into paid enrollments', function () {
    $team = Team::factory()->create();
    $manager = User::factory()->create();
    $course = Course::factory()->for($team)->create(['currency' => 'QAR', 'validity_days' => 180]);
    $package = CoursePackage::factory()->for($team)->for($course)->create([
        'currency' => 'QAR',
        'price' => 1500,
        'validity_days' => 90,
    ]);
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'pending',
            'payment_status' => 'pending',
            'access_starts_at' => null,
            'access_expires_at' => null,
        ]);

    $invoice = app(IssueInvoice::class)->handle($enrollment, [
        'number' => 'PX-INV-OPS-001',
        'subtotal' => 1500,
        'discount_total' => 0,
        'tax_total' => 0,
    ]);
    $payment = app(RecordManualPayment::class)->handle($invoice, [
        'amount' => 1500,
        'method' => 'bank_transfer',
        'reference' => 'BANK-123',
        'notes' => 'Proof received by finance.',
    ]);
    $approvedPayment = app(ApproveManualPayment::class)->handle($payment, $manager);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($payment)->toBeInstanceOf(PaymentTransaction::class)
        ->and($approvedPayment->status)->toBe('approved')
        ->and($approvedPayment->approvedBy->is($manager))->toBeTrue()
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->paid_at)->not->toBeNull()
        ->and($enrollment->fresh()->payment_status)->toBe('paid')
        ->and($enrollment->fresh()->status)->toBe('active')
        ->and($enrollment->fresh()->access_starts_at)->not->toBeNull()
        ->and($enrollment->fresh()->access_expires_at)->not->toBeNull()
        ->and(AuditEvent::where('action', 'payment.approved')->count())->toBe(1);

    $auditEvent = AuditEvent::where('action', 'payment.approved')->firstOrFail();

    expect($auditEvent->team->is($team))->toBeTrue()
        ->and($auditEvent->actor->is($manager))->toBeTrue()
        ->and($auditEvent->subject->is($approvedPayment))->toBeTrue()
        ->and($auditEvent->before['status'])->toBe('pending')
        ->and($auditEvent->after['status'])->toBe('approved')
        ->and($auditEvent->metadata['invoice_id'])->toBe($invoice->id)
        ->and($auditEvent->metadata['enrollment_id'])->toBe($enrollment->id);
});

it('records session attendance and practical assessment outcomes', function () {
    $team = Team::factory()->create();
    $instructor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($instructor, 'instructor')
        ->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();

    $attendance = app(RecordSessionAttendance::class)->handle($session, $enrollment, $instructor, [
        'status' => 'present',
        'notes' => 'Arrived on time.',
    ]);
    $updatedAttendance = app(RecordSessionAttendance::class)->handle($session, $enrollment, $instructor, [
        'status' => 'late',
        'notes' => 'Updated from instructor sheet.',
    ]);
    $assessment = app(RecordPracticalAssessment::class)->handle($updatedAttendance, $instructor, [
        'practical_outcome' => 'passed',
        'practical_score' => 92.5,
        'practical_comments' => 'Safe wiring and correct testing sequence.',
    ]);

    expect($attendance->id)->toBe($updatedAttendance->id)
        ->and($session->attendanceRecords()->count())->toBe(1)
        ->and($assessment->status)->toBe('late')
        ->and($assessment->practical_outcome)->toBe('passed')
        ->and($assessment->practical_score)->toBe('92.50')
        ->and($assessment->assessedBy->is($instructor))->toBeTrue();
});

it('rejects attendance for enrollments from another course', function () {
    $team = Team::factory()->create();
    $instructor = User::factory()->create();
    $sessionCourse = Course::factory()->for($team)->create();
    $otherCourse = Course::factory()->for($team)->create();
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($sessionCourse)
        ->for($instructor, 'instructor')
        ->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($otherCourse)
        ->create();

    app(RecordSessionAttendance::class)->handle($session, $enrollment, $instructor, [
        'status' => 'present',
    ]);
})->throws(ValidationException::class);
