<?php

use App\Actions\PowerX\ConvertLeadToEnrollment;
use App\Actions\PowerX\DecideEnrollmentAdmission;
use App\Enums\PowerXRole;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\RelationManagers\ActivitiesRelationManager;
use App\Models\AuditEvent;
use App\Models\Communication;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\FreePreviewEvent;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Lesson;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('lead actions create a visible follow up history and normalized CRM statuses', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $course = Course::factory()->for($team)->create();
    $lead = Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'status' => Lead::STATUS_NEW,
            'source' => 'campaign',
            'campaign' => 'kahramaa-june',
            'follow_up_at' => now()->addDay(),
        ]);

    $this->actingAs($salesUser);

    Livewire::test(ListLeads::class)
        ->assertTableActionVisible('logContact', $lead)
        ->callTableAction('logContact', $lead, [
            'channel' => 'phone',
            'notes' => 'Called and confirmed weekend preference.',
            'follow_up_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertNotified('Contact logged')
        ->callTableAction('qualify', $lead->fresh(), [
            'notes' => 'Candidate matches Kahramaa prep profile.',
        ])
        ->assertNotified('Lead qualified')
        ->callTableAction('sendQuotation', $lead->fresh(), [
            'notes' => 'Drafted package and timing follow-up.',
            'follow_up_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertNotified('Quotation follow-up drafted');

    $lead->refresh();

    expect($lead->status)->toBe(Lead::STATUS_QUOTATION_SENT)
        ->and($lead->outcome)->toBe('interested')
        ->and($lead->activities()->count())->toBe(3)
        ->and(LeadResource::getRelations())->toContain(ActivitiesRelationManager::class);

    $quotationActivity = LeadActivity::query()
        ->whereBelongsTo($lead)
        ->where('type', LeadActivity::TYPE_QUOTATION_SENT)
        ->firstOrFail();
    $draft = Communication::query()
        ->whereBelongsTo($lead)
        ->where('template_key', 'lead_follow_up')
        ->firstOrFail();
    $auditEvent = AuditEvent::query()
        ->where('action', 'lead.activity_recorded')
        ->whereMorphedTo('subject', $lead)
        ->get()
        ->first(fn (AuditEvent $event): bool => ($event->metadata['activity_id'] ?? null) === $quotationActivity->id);

    expect($quotationActivity->actor_id)->toBe($salesUser->id)
        ->and($quotationActivity->previous_status)->toBe(Lead::STATUS_QUALIFIED)
        ->and($quotationActivity->next_status)->toBe(Lead::STATUS_QUOTATION_SENT)
        ->and($draft->status)->toBe(Communication::STATUS_DRAFT)
        ->and($auditEvent)->toBeInstanceOf(AuditEvent::class);
});

test('lead actions can mark not responsive or lost', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $notResponsiveLead = Lead::factory()->for($team)->create(['status' => Lead::STATUS_CONTACTED]);
    $lostLead = Lead::factory()->for($team)->create(['status' => Lead::STATUS_QUALIFIED]);

    $this->actingAs($salesUser);

    Livewire::test(ListLeads::class)
        ->callTableAction('markNotResponsive', $notResponsiveLead)
        ->assertNotified('Lead marked not responsive')
        ->callTableAction('markLost', $lostLead, [
            'notes' => 'Chose another provider.',
        ])
        ->assertNotified('Lead marked lost');

    expect($notResponsiveLead->fresh()->status)->toBe(Lead::STATUS_NOT_RESPONSIVE)
        ->and($notResponsiveLead->fresh()->outcome)->toBe('no_response')
        ->and($lostLead->fresh()->status)->toBe(Lead::STATUS_LOST)
        ->and($lostLead->fresh()->outcome)->toBe('lost');
});

test('lead conversion creates an enrollment, conversion history, and registration confirmation draft', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $course = Course::factory()->for($team)->create();
    $package = CoursePackage::factory()->for($team)->for($course)->create(['is_active' => true]);
    $lead = Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'name' => 'Dana Malik',
            'email' => 'dana@example.test',
            'phone' => '+974 5000 1111',
            'status' => Lead::STATUS_QUALIFIED,
        ]);

    $this->actingAs($salesUser);

    Livewire::test(ListLeads::class)
        ->assertTableActionVisible('convertToEnrollment', $lead)
        ->callTableAction('convertToEnrollment', $lead, [
            'course_package_id' => $package->id,
            'notes' => 'Convert after quotation acceptance.',
        ])
        ->assertNotified('Lead converted to enrollment');

    $lead->refresh();
    $enrollment = Enrollment::query()->where('course_id', $course->id)->firstOrFail();

    expect($lead->status)->toBe(Lead::STATUS_ENROLLED)
        ->and($lead->outcome)->toBe('registered')
        ->and($lead->converted_at)->not->toBeNull()
        ->and($lead->metadata['conversion']['enrollment_id'])->toBe($enrollment->id)
        ->and($enrollment->studentProfile->full_name)->toBe('Dana Malik')
        ->and($enrollment->status)->toBe(Enrollment::STATUS_PENDING)
        ->and($enrollment->metadata['lead_id'])->toBe($lead->id);

    Communication::query()
        ->where('template_key', 'registration_confirmation')
        ->where('lead_id', $lead->id)
        ->where('student_profile_id', $enrollment->student_profile_id)
        ->firstOrFail();
});

test('lead conversion table action supports converting without a package', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $course = Course::factory()->for($team)->create();
    $lead = Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'status' => Lead::STATUS_QUALIFIED,
        ]);

    $this->actingAs($salesUser);

    Livewire::test(ListLeads::class)
        ->callTableAction('convertToEnrollment', $lead, [
            'course_package_id' => null,
            'notes' => 'Convert without package selection.',
        ])
        ->assertNotified('Lead converted to enrollment');

    $enrollment = Enrollment::query()->where('course_id', $course->id)->firstOrFail();

    expect($enrollment->course_package_id)->toBeNull()
        ->and($enrollment->notes)->toBe('Convert without package selection.');
});

test('lead conversion reuses existing profiles and validates course package fit', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $course = Course::factory()->for($team)->create();
    $otherCourse = Course::factory()->for($team)->create();
    $wrongPackage = CoursePackage::factory()->for($team)->for($otherCourse)->create(['is_active' => true]);
    $profile = StudentProfile::factory()
        ->for($team)
        ->create([
            'full_name' => 'Existing Student',
            'email' => 'existing@example.test',
        ]);
    $lead = Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'name' => 'Existing Student Lead',
            'email' => 'existing@example.test',
        ]);
    $leadWithoutCourse = Lead::factory()->for($team)->create(['course_id' => null]);

    expect(fn () => app(ConvertLeadToEnrollment::class)->handle($leadWithoutCourse, $salesUser))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(ConvertLeadToEnrollment::class)->handle($lead, $salesUser, $wrongPackage))
        ->toThrow(ValidationException::class);

    $enrollment = app(ConvertLeadToEnrollment::class)->handle($lead, $salesUser);

    expect($enrollment->student_profile_id)->toBe($profile->id)
        ->and($enrollment->course_package_id)->toBeNull()
        ->and(StudentProfile::query()->where('email', 'existing@example.test')->count())->toBe(1);
});

test('lead activity relation manager lists follow up history', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $lead = Lead::factory()->for($team)->for($salesUser, 'owner')->create();
    $activity = LeadActivity::factory()
        ->for($team)
        ->for($lead)
        ->for($salesUser, 'actor')
        ->create([
            'type' => LeadActivity::TYPE_CONTACT_LOGGED,
            'title' => 'Called student about weekend batch',
            'channel' => 'phone',
            'previous_status' => Lead::STATUS_NEW,
            'next_status' => Lead::STATUS_CONTACTED,
            'notes' => 'Interested in the next intake.',
        ]);

    $this->actingAs($salesUser);

    Livewire::test(ActivitiesRelationManager::class, [
        'ownerRecord' => $lead,
        'pageClass' => EditLead::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$activity])
        ->assertTableColumnStateSet('title', 'Called student about weekend batch', record: $activity)
        ->assertTableColumnStateSet('actor.name', $salesUser->name, record: $activity)
        ->assertTableColumnExists('notes');
});

test('phase one lead tracking models expose relationships and casts', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $course = Course::factory()->for($team)->create();
    $module = CourseModule::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module, 'courseModule')->create(['is_preview' => true]);
    $lead = Lead::factory()->for($team)->for($course)->for($salesUser, 'owner')->create();
    $activity = LeadActivity::factory()
        ->for($team)
        ->for($lead)
        ->for($salesUser, 'actor')
        ->create(['metadata' => ['touchpoint' => 'call']]);
    $event = FreePreviewEvent::factory()
        ->for($team)
        ->for($lead)
        ->for($salesUser, 'user')
        ->for($course)
        ->for($lesson)
        ->create([
            'metadata' => ['attribution' => ['utm_source' => 'newsletter']],
        ]);

    expect($activity->team()->getResults()->is($team))->toBeTrue()
        ->and($activity->lead()->getResults()->is($lead))->toBeTrue()
        ->and($activity->actor()->getResults()->is($salesUser))->toBeTrue()
        ->and($activity->metadata['touchpoint'])->toBe('call')
        ->and($event->team()->getResults()->is($team))->toBeTrue()
        ->and($event->lead()->getResults()->is($lead))->toBeTrue()
        ->and($event->user()->getResults()->is($salesUser))->toBeTrue()
        ->and($event->course()->getResults()->is($course))->toBeTrue()
        ->and($event->lesson()->getResults()->is($lesson))->toBeTrue()
        ->and($event->metadata['attribution']['utm_source'])->toBe('newsletter')
        ->and($event->occurred_at)->not->toBeNull()
        ->and($lead->activities()->whereKey($activity)->exists())->toBeTrue()
        ->and($lead->freePreviewEvents()->whereKey($event)->exists())->toBeTrue();
});

test('admissions actions audit approval, rejection, request information, and draft communications', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $studentUser = grantPowerXRole(User::factory()->create(), PowerXRole::Student);
    $studentUser->switchTeam($team);
    $team->members()->syncWithoutDetaching([$studentUser->id => ['role' => 'member']]);
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($studentUser, 'user')
        ->create(['full_name' => 'Mona Student']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($course)
        ->for($profile, 'studentProfile')
        ->create([
            'status' => Enrollment::STATUS_PENDING,
            'payment_status' => 'pending',
            'approved_by_id' => null,
            'approved_at' => null,
        ]);
    $rejectedEnrollment = Enrollment::factory()
        ->for($team)
        ->for($course)
        ->for(StudentProfile::factory()->for($team), 'studentProfile')
        ->create(['status' => Enrollment::STATUS_PENDING]);

    $this->actingAs($salesUser);

    Livewire::test(ListEnrollments::class)
        ->assertTableActionVisible('requestInformation', $enrollment)
        ->callTableAction('requestInformation', $enrollment, [
            'notes' => 'Upload Qatar ID and employer details.',
        ])
        ->assertNotified('Information requested')
        ->assertTableActionVisible('approveAdmission', $enrollment->fresh())
        ->callTableAction('approveAdmission', $enrollment->fresh())
        ->assertNotified('Enrollment approved')
        ->callTableAction('rejectAdmission', $rejectedEnrollment, [
            'notes' => 'Course prerequisite not met.',
        ])
        ->assertNotified('Enrollment rejected');

    $enrollment->refresh();
    $rejectedEnrollment->refresh();

    expect($enrollment->status)->toBe(Enrollment::STATUS_APPROVED)
        ->and($enrollment->approved_by_id)->toBe($salesUser->id)
        ->and($enrollment->approved_at)->not->toBeNull()
        ->and($enrollment->metadata['admission']['decision'])->toBe('approve')
        ->and($rejectedEnrollment->status)->toBe(Enrollment::STATUS_REJECTED);

    $requestInfoAudit = AuditEvent::query()
        ->where('action', 'enrollment.information_requested')
        ->whereMorphedTo('subject', $enrollment)
        ->firstOrFail();
    $approvalAudit = AuditEvent::query()
        ->where('action', 'enrollment.approved')
        ->whereMorphedTo('subject', $enrollment)
        ->firstOrFail();
    $rejectionAudit = AuditEvent::query()
        ->where('action', 'enrollment.rejected')
        ->whereMorphedTo('subject', $rejectedEnrollment)
        ->firstOrFail();

    expect($requestInfoAudit->actor_id)->toBe($salesUser->id)
        ->and($requestInfoAudit->before['status'])->toBe(Enrollment::STATUS_PENDING)
        ->and($requestInfoAudit->after['status'])->toBe(Enrollment::STATUS_REQUEST_MORE_INFORMATION)
        ->and($approvalAudit->before['status'])->toBe(Enrollment::STATUS_REQUEST_MORE_INFORMATION)
        ->and($approvalAudit->after['status'])->toBe(Enrollment::STATUS_APPROVED)
        ->and($rejectionAudit->after['status'])->toBe(Enrollment::STATUS_REJECTED);

    expect(Communication::query()->where('template_key', 'enrollment_request_information')->where('student_profile_id', $profile->id)->exists())->toBeTrue()
        ->and(Communication::query()->where('template_key', 'enrollment_approved')->where('student_profile_id', $profile->id)->exists())->toBeTrue()
        ->and(Communication::query()->where('template_key', 'enrollment_rejected')->where('student_profile_id', $rejectedEnrollment->student_profile_id)->exists())->toBeTrue();

    $this
        ->actingAs($studentUser)
        ->get(route('student.courses.index', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.status', Enrollment::STATUS_APPROVED)
            ->where('enrollments.0.accessStatus', 'payment_pending'));
});

test('admissions rejects unsupported decisions before mutating enrollment state', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);
    $team = $salesUser->currentTeam;
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for(Course::factory()->for($team))
        ->for(StudentProfile::factory()->for($team), 'studentProfile')
        ->create(['status' => Enrollment::STATUS_PENDING]);

    expect(fn () => app(DecideEnrollmentAdmission::class)->handle($enrollment, $salesUser, 'defer'))
        ->toThrow(ValidationException::class);

    expect($enrollment->fresh()->status)->toBe(Enrollment::STATUS_PENDING)
        ->and(AuditEvent::query()->whereMorphedTo('subject', $enrollment)->exists())->toBeFalse()
        ->and(Communication::query()->where('student_profile_id', $enrollment->student_profile_id)->exists())->toBeFalse();
});

test('student portal exposes admission pending request information and rejected states', function (): void {
    $studentUser = grantPowerXRole(User::factory()->create(), PowerXRole::Student);
    $team = $studentUser->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($studentUser, 'user')
        ->create();
    $pendingCourse = Course::factory()->for($team)->create(['title' => 'Pending Admission']);
    $requestInfoCourse = Course::factory()->for($team)->create(['title' => 'Need More Info']);
    $rejectedCourse = Course::factory()->for($team)->create(['title' => 'Rejected Admission']);

    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($pendingCourse)
        ->create(['status' => Enrollment::STATUS_PENDING, 'payment_status' => 'pending']);
    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($requestInfoCourse)
        ->create(['status' => Enrollment::STATUS_REQUEST_MORE_INFORMATION, 'payment_status' => 'pending']);
    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($rejectedCourse)
        ->create(['status' => Enrollment::STATUS_REJECTED, 'payment_status' => 'pending']);

    $this
        ->actingAs($studentUser)
        ->get(route('student.courses.index', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.accessStatus', 'admission_rejected')
            ->where('enrollments.1.accessStatus', 'information_requested')
            ->where('enrollments.2.accessStatus', 'admission_pending'));
});
