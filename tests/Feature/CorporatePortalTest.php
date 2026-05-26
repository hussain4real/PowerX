<?php

use App\Enums\PowerXRole;
use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Company;
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
use Database\Seeders\PowerXAccessSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('corporate portal exposes only matched company read only records', function (): void {
    $this->withoutVite();

    [$corporate, $team] = corporatePortalFixtures();

    $response = $this
        ->actingAs($corporate)
        ->get(route('corporate.portal', ['current_team' => $team]));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Corporate/Portal')
            ->where('companies.0.name', 'Visible Facilities LLC')
            ->where('summary.companyCount', 1)
            ->where('summary.employeeCount', 1)
            ->where('summary.enrollmentCount', 1)
            ->where('summary.completedEnrollments', 1)
            ->where('summary.attendanceRate', 100)
            ->where('summary.certificateCount', 1)
            ->where('quotations.0.number', 'PX-QUO-CORP-001')
            ->where('enrollments.0.studentName', 'Visible Employee')
            ->where('finance.invoices.0.number', 'PX-INV-CORP-001')
            ->where('finance.payments.0.method', 'bank_transfer')
            ->where('attendance.0.studentName', 'Visible Employee')
            ->where('certificates.0.certificateNumber', 'PX-CERT-CORP-001')
            ->where('report.available', true)
            ->where('dataSharingGate.status', 'pending_sign_off'));

    $response
        ->assertDontSee('Hidden Employee')
        ->assertDontSee('PX-QUO-HIDDEN')
        ->assertDontSee('private.employee@example.test')
        ->assertDontSee('+97455599999');

    $this
        ->actingAs($corporate)
        ->get(route('reports.operational.csv', ['current_team' => $team]))
        ->assertForbidden();
});

test('corporate coordinator can download a company scoped csv report', function (): void {
    [$corporate, $team] = corporatePortalFixtures();

    $response = $this
        ->actingAs($corporate)
        ->get(route('corporate.portal.report.csv', ['current_team' => $team]));

    $response->assertSuccessful();
    $response->assertDownload(str($team->name)->slug().'-corporate-portal-report.csv');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('PowerX Corporate Coordinator Report')
        ->toContain('Visible Facilities LLC')
        ->toContain('PX-QUO-CORP-001')
        ->toContain('Visible Employee')
        ->toContain('PX-CERT-CORP-001')
        ->toContain('Pending PowerX sign-off')
        ->not->toContain('Hidden Employee')
        ->not->toContain('PX-QUO-HIDDEN')
        ->not->toContain('private.employee@example.test')
        ->not->toContain('+97455599999');
});

test('corporate csv report neutralizes spreadsheet formula cells', function (): void {
    [$corporate, $team] = corporatePortalFixtures();

    Company::query()
        ->where('name', 'Visible Facilities LLC')
        ->update([
            'name' => '=Danger Corp',
            'email' => '',
        ]);

    $content = $this
        ->actingAs($corporate)
        ->get(route('corporate.portal.report.csv', ['current_team' => $team]))
        ->streamedContent();

    expect($content)
        ->toContain('"\'=Danger Corp"')
        ->not->toContain(',=Danger Corp')
        ->not->toContain("\n=Danger Corp");
});

test('corporate report download stays locked until a company match exists', function (): void {
    $this->withoutVite();

    $corporate = grantPowerXRole(User::factory()->create(['name' => 'Unmatched Coordinator']), PowerXRole::Corporate);
    $team = $corporate->currentTeam;

    Company::factory()
        ->for($team)
        ->create([
            'name' => 'Unmatched Company',
            'contact_name' => 'Different Coordinator',
            'email' => 'different@example.test',
        ]);

    $this
        ->actingAs($corporate)
        ->get(route('corporate.portal', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Corporate/Portal')
            ->has('companies', 0)
            ->where('report.available', false));

    $this
        ->actingAs($corporate)
        ->get(route('corporate.portal.report.csv', ['current_team' => $team]))
        ->assertForbidden();
});

/**
 * @return array{0: User, 1: Team}
 */
function corporatePortalFixtures(): array
{
    $corporate = grantPowerXRole(User::factory()->create(['name' => 'Karim Corporate']), PowerXRole::Corporate);
    $team = $corporate->currentTeam;
    $company = Company::factory()
        ->for($team)
        ->create([
            'name' => 'Visible Facilities LLC',
            'contact_name' => 'Karim Corporate',
            'email' => 'training@visible.test',
            'phone' => '+97455500000',
            'metadata' => [
                'coordinator_email' => $corporate->email,
                'industry' => 'facility management',
            ],
        ]);
    $hiddenCompany = Company::factory()
        ->for($team)
        ->create([
            'name' => 'Hidden Switchgear LLC',
            'contact_name' => 'Karim Corporate',
            'email' => 'hidden@example.test',
        ]);
    $course = Course::factory()->for($team)->create(['title' => 'Corporate Safety']);
    $package = CoursePackage::factory()->for($team)->for($course)->create(['name' => 'Corporate Team']);
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($company)
        ->create([
            'full_name' => 'Visible Employee',
            'email' => 'private.employee@example.test',
            'mobile' => '+97455599999',
            'profession' => 'Technician',
        ]);
    $hiddenProfile = StudentProfile::factory()
        ->for($team)
        ->for($hiddenCompany)
        ->create(['full_name' => 'Hidden Employee']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'completed',
            'payment_status' => 'paid',
            'approved_at' => now()->subMonth(),
        ]);

    Enrollment::factory()
        ->for($team)
        ->for($hiddenCompany)
        ->for($hiddenProfile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create(['status' => 'active']);

    Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create([
            'number' => 'PX-QUO-CORP-001',
            'type' => 'quotation',
            'status' => 'paid',
            'currency' => 'QAR',
            'subtotal' => 2500,
            'total' => 2500,
            'issued_at' => now()->subWeeks(3),
            'paid_at' => now()->subWeeks(2),
            'metadata' => ['employee_count' => 1],
        ]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create([
            'number' => 'PX-INV-CORP-001',
            'type' => 'invoice',
            'status' => 'paid',
            'currency' => 'QAR',
            'subtotal' => 2500,
            'total' => 2500,
            'issued_at' => now(),
            'paid_at' => now(),
        ]);
    Invoice::factory()
        ->for($team)
        ->for($hiddenCompany)
        ->for($hiddenProfile, 'studentProfile')
        ->create([
            'number' => 'PX-QUO-HIDDEN',
            'type' => 'quotation',
            'status' => 'issued',
        ]);

    PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->for($invoice)
        ->create([
            'method' => 'bank_transfer',
            'status' => 'approved',
            'currency' => 'QAR',
            'amount' => 2500,
            'paid_at' => now(),
            'approved_at' => now(),
            'reference' => 'PX-PAY-CORP-001',
        ]);

    $batch = TrainingBatch::factory()->for($team)->for($course)->create(['name' => 'CORP-BATCH-01']);
    $session = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create([
            'title' => 'Corporate practical',
            'session_type' => 'practical',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subWeek()->addHours(2),
        ]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->create([
            'status' => 'present',
            'practical_outcome' => 'passed',
            'practical_score' => 94,
            'attended_at' => now()->subWeek(),
        ]);

    Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create([
            'certificate_number' => 'PX-CERT-CORP-001',
            'verification_token' => 'visible-corporate-certificate',
            'status' => 'issued',
            'result' => 'passed',
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

    return [$corporate, $team];
}
