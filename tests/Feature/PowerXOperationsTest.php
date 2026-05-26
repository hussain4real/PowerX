<?php

use App\Actions\PowerX\ApproveManualPayment;
use App\Actions\PowerX\BuildStudentPortal;
use App\Actions\PowerX\CancelTrainingSession;
use App\Actions\PowerX\IssueInvoice;
use App\Actions\PowerX\RecordMakeUpClass;
use App\Actions\PowerX\RecordManualPayment;
use App\Actions\PowerX\RecordPracticalAssessment;
use App\Actions\PowerX\RecordSessionAttendance;
use App\Actions\PowerX\RescheduleTrainingSession;
use App\Actions\PowerX\TransferEnrollmentToBatch;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Communication;
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

it('reschedules and cancels training sessions with audit and communication drafts', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create([
        'full_name' => 'Aisha Candidate',
        'mobile' => '+974 5011 2233',
    ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['name' => 'PX-OPS-01']);
    $session = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create([
            'title' => 'Practical wiring lab',
            'venue' => 'Old lab',
            'starts_at' => now()->addDays(2)->setTime(18, 0),
            'ends_at' => now()->addDays(2)->setTime(20, 0),
        ]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'pending']);

    $rescheduled = app(RescheduleTrainingSession::class)->handle($session, $actor, [
        'starts_at' => now()->addDays(3)->setTime(19, 0)->toDateTimeString(),
        'ends_at' => now()->addDays(3)->setTime(21, 0)->toDateTimeString(),
        'venue' => 'PowerX workshop',
        'reason' => 'Instructor availability',
    ]);
    $cancelled = app(CancelTrainingSession::class)->handle($rescheduled, $actor, [
        'reason' => 'Venue maintenance',
    ]);

    expect($rescheduled->venue)->toBe('PowerX workshop')
        ->and($rescheduled->metadata['reschedule_reason'])->toBe('Instructor availability')
        ->and($rescheduled->metadata['reschedule_history'][0]['venue'])->toBe('Old lab')
        ->and($cancelled->status)->toBe('cancelled')
        ->and($cancelled->metadata['cancellation_reason'])->toBe('Venue maintenance')
        ->and(AuditEvent::where('action', 'training_session.rescheduled')->count())->toBe(1)
        ->and(AuditEvent::where('action', 'training_session.cancelled')->count())->toBe(1)
        ->and(Communication::where('template_key', 'class_schedule_changed')->first()?->message)->toContain('PowerX schedule update')
        ->and(Communication::where('template_key', 'class_cancelled')->first()?->message)->toContain('Venue maintenance');
});

it('rejects invalid reschedule windows', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create();
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create();

    expect(fn () => app(RescheduleTrainingSession::class)->handle($session, $actor, [
        'starts_at' => null,
        'ends_at' => now()->addHour(),
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(RescheduleTrainingSession::class)->handle($session, $actor, [
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHour(),
    ]))->toThrow(ValidationException::class);
});

it('transfers enrollments between batches and keeps portals schedule consistent', function () {
    $studentUser = User::factory()->create();
    $actor = User::factory()->create(['name' => 'Training Coordinator']);
    $team = $studentUser->currentTeam;
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Prep']);
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($studentUser)
        ->create(['full_name' => 'Aisha Candidate', 'mobile' => '+974 5011 2233']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $sourceBatch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['name' => 'PX-WEEKDAY-01']);
    $sourcePastSession = TrainingSession::factory()
        ->for($sourceBatch, 'trainingBatch')
        ->create(['title' => 'Completed theory', 'starts_at' => now()->subDay()]);
    $sourceFutureSession = TrainingSession::factory()
        ->for($sourceBatch, 'trainingBatch')
        ->create(['title' => 'Old practical', 'starts_at' => now()->addDay()]);
    $pastAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($sourcePastSession, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'present']);
    $futureAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($sourceFutureSession, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'pending']);
    $targetBatch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['name' => 'PX-WEEKEND-02', 'capacity' => 3, 'venue' => 'PowerX workshop']);
    $targetTheorySession = TrainingSession::factory()
        ->for($targetBatch, 'trainingBatch')
        ->create(['title' => 'Weekend theory', 'starts_at' => now()->addDays(2)->setTime(9, 0)]);
    TrainingSession::factory()
        ->for($targetBatch, 'trainingBatch')
        ->create(['title' => 'Weekend practical', 'starts_at' => now()->addDays(3)->setTime(9, 0)]);
    $deletedTargetAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($targetTheorySession, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'pending']);
    $deletedTargetAttendance->delete();

    $targetAttendance = app(TransferEnrollmentToBatch::class)->handle($enrollment, $targetBatch, $actor, [
        'reason' => 'Student requested weekend batch',
    ]);

    $portal = app(BuildStudentPortal::class)->handle($studentUser, $team);

    expect($targetAttendance)->toHaveCount(2)
        ->and($targetAttendance->pluck('status')->all())->toBe(['pending', 'pending'])
        ->and(AttendanceRecord::withTrashed()->find($deletedTargetAttendance->id)->trashed())->toBeFalse()
        ->and(AttendanceRecord::withTrashed()->find($futureAttendance->id)->trashed())->toBeTrue()
        ->and(AttendanceRecord::find($pastAttendance->id)?->status)->toBe('present')
        ->and($enrollment->fresh()->metadata['current_training_batch_id'])->toBe($targetBatch->id)
        ->and($portal['summary']['nextSessionLabel'])->toBe('Weekend theory')
        ->and(collect($portal['enrollments'][0]['schedule'])->pluck('title')->all())->toBe([
            'Completed theory',
            'Weekend theory',
            'Weekend practical',
        ])
        ->and(Communication::where('template_key', 'batch_transfer')->first()?->message)->toContain('PX-WEEKEND-02')
        ->and(AuditEvent::where('action', 'enrollment.transferred')->count())->toBe(1);
});

it('rejects invalid batch transfers', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create();
    $fullBatch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['capacity' => 1]);
    $fullSession = TrainingSession::factory()->for($fullBatch, 'trainingBatch')->create();
    $existingEnrollment = Enrollment::factory()->for($team)->for($course)->create();
    AttendanceRecord::factory()
        ->for($team)
        ->for($fullSession, 'trainingSession')
        ->for($existingEnrollment)
        ->create();

    expect(fn () => app(TransferEnrollmentToBatch::class)->handle($enrollment, $fullBatch, $actor))
        ->toThrow(ValidationException::class);
});

it('rejects batch transfers across teams and courses', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $otherCourse = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create();
    $wrongCourseBatch = TrainingBatch::factory()
        ->for($team)
        ->for($otherCourse)
        ->for($actor, 'instructor')
        ->create();
    $otherTeamBatch = TrainingBatch::factory()
        ->for(Team::factory(), 'team')
        ->for($course)
        ->for($actor, 'instructor')
        ->create();

    expect(fn () => app(TransferEnrollmentToBatch::class)->handle($enrollment, $wrongCourseBatch, $actor))
        ->toThrow(ValidationException::class);
    expect(fn () => app(TransferEnrollmentToBatch::class)->handle($enrollment, $otherTeamBatch, $actor))
        ->toThrow(ValidationException::class);
});

it('records make-up classes and links the missed attendance record', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create([
        'full_name' => 'Aisha Candidate',
        'mobile' => '+974 5011 2233',
    ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['capacity' => 2]);
    $missedSession = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Missed practical', 'starts_at' => now()->subDay()]);
    $makeUpSession = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Make-up practical', 'starts_at' => now()->addDays(2)]);
    $restoredMakeUpSession = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Restored make-up practical', 'starts_at' => now()->addDays(3)]);
    $missedAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($missedSession, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'absent']);

    $makeUpAttendance = app(RecordMakeUpClass::class)->handle($makeUpSession, $enrollment, $actor, [
        'missed_attendance_record_id' => $missedAttendance->id,
        'reason' => 'Excused absence',
        'notes' => 'Student submitted medical note.',
    ]);
    $deletedMakeUpAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($restoredMakeUpSession, 'trainingSession')
        ->for($enrollment)
        ->create(['status' => 'pending']);
    $deletedMakeUpAttendance->delete();
    $restoredMakeUpAttendance = app(RecordMakeUpClass::class)->handle($restoredMakeUpSession, $enrollment, $actor, [
        'reason' => 'Optional revision class',
        'status' => 'present',
    ]);

    expect($makeUpAttendance->status)->toBe('pending')
        ->and($makeUpAttendance->metadata['make_up_class']['missed_attendance_record_id'])->toBe($missedAttendance->id)
        ->and($restoredMakeUpAttendance->id)->toBe($deletedMakeUpAttendance->id)
        ->and($restoredMakeUpAttendance->status)->toBe('present')
        ->and($restoredMakeUpAttendance->metadata['make_up_class']['missed_attendance_record_id'])->toBeNull()
        ->and(AttendanceRecord::withTrashed()->find($deletedMakeUpAttendance->id)->trashed())->toBeFalse()
        ->and($missedAttendance->fresh()->metadata['make_up_attendance_record_id'])->toBe($makeUpAttendance->id)
        ->and(Communication::where('template_key', 'make_up_class')->first()?->message)->toContain('Make-up practical')
        ->and(AuditEvent::where('action', 'attendance.make_up_recorded')->count())->toBe(2);
});

it('rejects invalid make-up class assignments', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $course = Course::factory()->for($team)->create();
    $otherCourse = Course::factory()->for($team)->create();
    $enrollment = Enrollment::factory()->for($team)->for($course)->create();
    $wrongCourseBatch = TrainingBatch::factory()
        ->for($team)
        ->for($otherCourse)
        ->for($actor, 'instructor')
        ->create();
    $wrongCourseSession = TrainingSession::factory()->for($wrongCourseBatch, 'trainingBatch')->create();
    $otherTeamSession = TrainingSession::factory()
        ->for(TrainingBatch::factory()->for(Team::factory(), 'team')->for($course)->for($actor, 'instructor'), 'trainingBatch')
        ->create();
    $fullBatch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['capacity' => 1]);
    $fullSession = TrainingSession::factory()->for($fullBatch, 'trainingBatch')->create();
    AttendanceRecord::factory()
        ->for($team)
        ->for($fullSession, 'trainingSession')
        ->for(Enrollment::factory()->for($team)->for($course))
        ->create();
    $missedSession = TrainingSession::factory()->for($fullBatch, 'trainingBatch')->create();
    $missedAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($missedSession, 'trainingSession')
        ->for(Enrollment::factory()->for($team)->for($course))
        ->create();
    $validMakeUpBatch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($actor, 'instructor')
        ->create(['capacity' => 2]);
    $validMakeUpSession = TrainingSession::factory()->for($validMakeUpBatch, 'trainingBatch')->create();
    $wrongCourseMissedAttendance = AttendanceRecord::factory()
        ->for($team)
        ->for($wrongCourseSession, 'trainingSession')
        ->for($enrollment)
        ->create();

    expect(fn () => app(RecordMakeUpClass::class)->handle($wrongCourseSession, $enrollment, $actor))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordMakeUpClass::class)->handle($otherTeamSession, $enrollment, $actor))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordMakeUpClass::class)->handle($fullSession, $enrollment, $actor))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordMakeUpClass::class)->handle($validMakeUpSession, $enrollment, $actor, [
        'missed_attendance_record_id' => $missedAttendance->id,
    ]))->toThrow(ValidationException::class);
    expect(fn () => app(RecordMakeUpClass::class)->handle($validMakeUpSession, $enrollment, $actor, [
        'missed_attendance_record_id' => $wrongCourseMissedAttendance->id,
    ]))->toThrow(ValidationException::class);
});
