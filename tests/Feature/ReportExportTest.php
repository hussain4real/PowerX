<?php

use App\Actions\PowerX\BuildOperationalReport;
use App\Enums\PowerXRole;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('authorized staff can download operational reports as csv', function (): void {
    [$manager, $team] = powerxReportFixtures();

    $response = $this
        ->actingAs($manager)
        ->get(route('reports.operational.csv', ['current_team' => $team->slug]));

    $response->assertSuccessful();
    $response->assertDownload(str($team->name)->slug().'-operational-report.csv');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('PowerX Operational Report')
        ->toContain('Lead source report')
        ->toContain('Website Blitz')
        ->toContain('Sales pipeline report')
        ->toContain('Weekly revenue report')
        ->toContain('QAR 1,200.00')
        ->toContain('Course enrollment report')
        ->toContain('Attendance and practical report')
        ->toContain('Exam performance report')
        ->toContain('PX-REPORT-001')
        ->toContain('Reporting Manager')
        ->toContain('Exam analytics report')
        ->toContain('Switchgear safety')
        ->toContain('Certificate report')
        ->toContain('Corporate account report')
        ->not->toContain('Other Team Course');

    $auditEvent = AuditEvent::query()
        ->where('action', 'report.exported')
        ->where('team_id', $team->id)
        ->first();

    expect($auditEvent)->not->toBeNull()
        ->and($auditEvent->after['format'])->toBe('csv')
        ->and($auditEvent->after['sections'])->toContain('lead_source', 'corporate_account');
});

test('authorized staff can preview operational reports as pdf', function (): void {
    Pdf::fake();

    [$manager, $team] = powerxReportFixtures();

    $this
        ->actingAs($manager)
        ->get(route('reports.operational.pdf', ['current_team' => $team->slug]))
        ->assertSuccessful();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($team): bool {
        return $pdf->viewName === 'pdf.powerx.operational-report'
            && $pdf->viewData['report']['team'] === $team->name
            && collect($pdf->viewData['report']['sections'])->pluck('key')->contains('weekly_revenue')
            && str_contains($pdf->getHtml(), 'PowerX management export')
            && $pdf->isInline()
            && $pdf->downloadName === str($team->name)->slug().'-operational-report.pdf';
    });

    expect(AuditEvent::query()
        ->where('action', 'report.exported')
        ->where('team_id', $team->id)
        ->first()
        ?->after['format'])->toBe('pdf');
});

test('students cannot export operational reports', function (): void {
    $student = User::factory()->create();
    $student->assignRole(PowerXRole::Student->value);

    $this
        ->actingAs($student)
        ->get(route('reports.operational.csv', ['current_team' => $student->currentTeam()->firstOrFail()->slug]))
        ->assertForbidden();
});

test('operational report builder returns empty sections for new teams', function (): void {
    $team = Team::factory()->create(['name' => 'Empty Reporting Team']);
    $report = app(BuildOperationalReport::class)->handle($team);

    expect($report['title'])->toBe('PowerX Operational Report')
        ->and($report['team'])->toBe('Empty Reporting Team')
        ->and($report['sections'])->toHaveCount(9)
        ->and($report['sections'][0]['rows'])->toBe([])
        ->and($report['sections'][8]['rows'])->toBe([]);
});

test('operational report attributes campaign revenue and roi from internal records', function (): void {
    [$manager, $team] = powerxReportFixtures();

    $report = app(BuildOperationalReport::class)->handle($team);
    $leadSource = collect($report['sections'])->firstWhere('key', 'lead_source');
    $campaign = collect($leadSource['rows'])->firstWhere('Campaign', 'Website Blitz');

    expect($campaign)->toMatchArray([
        'Source' => 'website',
        'Campaign' => 'Website Blitz',
        'Lead count' => '2',
        'Qualified count' => '1',
        'Converted count' => '1',
        'Conversion rate' => '50.0%',
        'Cost' => 'QAR 300.00',
        'Revenue' => 'QAR 1,200.00',
        'ROI' => '300.0%',
        'Attribution' => config('powerx_growth.campaigns.attribution_status'),
    ])
        ->and($manager->exists)->toBeTrue();
});

/**
 * @return array{0: User, 1: Team}
 */
function powerxReportFixtures(): array
{
    $manager = User::factory()->create(['name' => 'Reporting Manager']);
    $manager->assignRole(PowerXRole::Management->value);
    $team = $manager->currentTeam()->firstOrFail();
    $company = Company::factory()->for($team)->create([
        'name' => 'Doha Electrical Works',
        'contact_name' => 'Noura Coordinator',
    ]);
    $course = Course::factory()->for($team)->create([
        'title' => 'Kahramaa Exam Preparation',
        'slug' => 'kahramaa-exam-preparation',
    ]);
    Course::factory()->create(['title' => 'Other Team Course']);
    $package = CoursePackage::factory()->for($team)->for($course)->create(['name' => 'Professional']);
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($company)
        ->create(['full_name' => 'Aisha Candidate']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    Enrollment::factory()
        ->for($team)
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'pending',
        ]);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($manager, 'instructor')
        ->create([
            'name' => 'PX-REPORT-001',
            'capacity' => 8,
        ]);
    $session = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Practical Lab']);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create([
            'type' => 'quotation',
            'status' => 'paid',
            'currency' => 'QAR',
            'subtotal' => 1500,
            'total' => 1500,
        ]);
    PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->for($invoice)
        ->for($manager, 'approvedBy')
        ->create([
            'method' => 'bank_transfer',
            'status' => 'approved',
            'currency' => 'QAR',
            'amount' => 1200,
            'paid_at' => now()->setTime(10, 30),
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->for($company)
        ->for($manager, 'owner')
        ->create([
            'name' => 'Qualified Lead',
            'source' => 'website',
            'campaign' => 'Website Blitz',
            'status' => 'qualified',
            'course_interest' => 'Kahramaa',
            'metadata' => [
                'quotation_number' => 'PX-QUO-REPORT',
                'payment_status' => 'quotation sent',
                'campaign_cost' => 300,
                'campaign_cost_currency' => 'QAR',
            ],
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->for($company)
        ->for($manager, 'owner')
        ->create([
            'name' => 'Converted Lead',
            'source' => 'website',
            'campaign' => 'Website Blitz',
            'status' => 'converted',
            'metadata' => [
                'quotation_number' => 'PX-QUO-REPORT',
                'payment_status' => 'paid',
            ],
        ]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($session, 'trainingSession')
        ->for($enrollment)
        ->create([
            'status' => 'present',
            'practical_outcome' => 'passed',
            'practical_score' => 92,
        ]);
    $exam = Exam::factory()->for($team)->for($course)->create(['title' => 'Final Mock Exam']);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create([
            'attempt_number' => 2,
            'score' => 86,
            'result' => 'passed',
            'metadata' => ['weak_topic' => 'Switchgear safety'],
        ]);
    Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($manager, 'approvedBy')
        ->create([
            'certificate_number' => 'PX-CERT-REPORT-001',
            'status' => 'issued',
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

    return [$manager, $manager->currentTeam()->firstOrFail()];
}
