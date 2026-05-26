<?php

use App\Actions\PowerX\BuildRenewalGrowthOpportunities;
use App\Actions\PowerX\CreateRenewalReminderCommunication;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Company;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use Carbon\CarbonImmutable;

test('renewal growth opportunities include expiring certificates recommendations and campaign performance', function (): void {
    $now = CarbonImmutable::parse('2026-05-25 10:00:00');
    $this->travelTo($now);

    [$team] = powerxRenewalFixtures($now);

    $opportunities = app(BuildRenewalGrowthOpportunities::class)->handle($team);

    expect($opportunities['summary'])->toMatchArray([
        'renewal_count' => 2,
        'overdue_count' => 1,
        'campaign_count' => 1,
    ])
        ->and($opportunities['renewals'][0]['certificateNumber'])->toBe('PX-CERT-EXPIRED')
        ->and($opportunities['renewals'][1]['certificateNumber'])->toBe('PX-CERT-RENEW')
        ->and($opportunities['renewals'][1]['studentName'])->toBe('Aisha Candidate')
        ->and($opportunities['renewals'][1]['companyName'])->toBe('Doha Electrical Works')
        ->and($opportunities['renewals'][1]['daysUntilExpiry'])->toBe(45)
        ->and($opportunities['renewals'][1]['recommendedCourses'][0]['title'])->toBe('Advanced Power Distribution')
        ->and($opportunities['campaigns'][0])->toMatchArray([
            'source' => 'referral',
            'campaign' => 'contractor-alumni',
            'leadCount' => 2,
            'qualifiedCount' => 1,
            'convertedCount' => 1,
            'conversionRate' => '50.0%',
            'costLabel' => 'QAR 300.00',
            'revenueLabel' => 'QAR 1,200.00',
            'roiLabel' => '300.0%',
            'attributionStatus' => 'Blocked - internal CRM/finance attribution only',
        ]);
});

test('renewal reminders create scheduled whatsapp ready communication drafts', function (): void {
    $now = CarbonImmutable::parse('2026-05-25 10:00:00');
    $this->travelTo($now);

    [$team, $certificate] = powerxRenewalFixtures($now);

    $communication = app(CreateRenewalReminderCommunication::class)->handle($certificate);

    expect($communication)->toBeInstanceOf(Communication::class)
        ->and($communication->team_id)->toBe($team->id)
        ->and($communication->student_profile_id)->toBe($certificate->student_profile_id)
        ->and($communication->company_id)->toBe($certificate->studentProfile->company_id)
        ->and($communication->channel)->toBe(Communication::CHANNEL_WHATSAPP)
        ->and($communication->template_key)->toBe('renewal_reminder')
        ->and($communication->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and($communication->message)->toContain('Kahramaa Exam Preparation')
        ->and($communication->scheduled_at->toDateString())->toBe('2026-06-09')
        ->and($communication->metadata['certificate_number'])->toBe('PX-CERT-RENEW')
        ->and($communication->metadata['recommended_courses'])->toBe(['Advanced Power Distribution', 'Industrial Safety Refresher'])
        ->and($communication->metadata['days_until_expiry'])->toBe(45)
        ->and($communication->metadata['whatsapp_url'])->toStartWith('https://wa.me/97450112233?text=');
});

/**
 * @return array{0: Team, 1: Certificate}
 */
function powerxRenewalFixtures(CarbonImmutable $now): array
{
    $team = Team::factory()->create(['name' => 'PowerX Renewals']);
    $company = Company::factory()->for($team)->create([
        'name' => 'Doha Electrical Works',
        'contact_name' => 'Noura Coordinator',
        'phone' => '+974 4499 0000',
    ]);
    $currentCourse = Course::factory()->for($team)->create([
        'title' => 'Kahramaa Exam Preparation',
        'category' => 'Kahramaa',
        'status' => 'published',
        'published_at' => $now->subDay(),
    ]);
    Course::factory()->for($team)->create([
        'title' => 'Advanced Power Distribution',
        'category' => 'Kahramaa',
        'status' => 'published',
        'published_at' => $now->subDay(),
    ]);
    Course::factory()->for($team)->create([
        'title' => 'Industrial Safety Refresher',
        'category' => 'Safety',
        'status' => 'published',
        'published_at' => $now->subDay(),
    ]);
    Course::factory()->for($team)->create([
        'title' => 'Draft Course Hidden From Recommendations',
        'category' => 'Kahramaa',
        'status' => 'draft',
        'published_at' => null,
    ]);
    $profile = StudentProfile::factory()->for($team)->for($company)->create([
        'full_name' => 'Aisha Candidate',
        'mobile' => '+974 5011 2233',
    ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($currentCourse, 'course')
        ->create(['status' => 'completed', 'payment_status' => 'paid']);
    $certificate = Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($currentCourse, 'course')
        ->create([
            'certificate_number' => 'PX-CERT-RENEW',
            'status' => 'issued',
            'expires_at' => $now->addDays(45),
        ]);
    Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($currentCourse, 'course')
        ->create([
            'certificate_number' => 'PX-CERT-EXPIRED',
            'status' => 'issued',
            'expires_at' => $now->subDays(2),
        ]);
    Certificate::factory()->create([
        'certificate_number' => 'PX-CERT-OTHER-TEAM',
        'status' => 'issued',
        'expires_at' => $now->addDays(10),
    ]);
    Lead::factory()
        ->for($team)
        ->for($company)
        ->for($currentCourse)
        ->create([
            'source' => 'referral',
            'campaign' => 'contractor-alumni',
            'status' => 'qualified',
            'metadata' => ['campaign_cost' => 300, 'campaign_cost_currency' => 'QAR'],
        ]);
    Lead::factory()
        ->for($team)
        ->for($company)
        ->for($currentCourse)
        ->create([
            'email' => $profile->email,
            'phone' => $profile->mobile,
            'source' => 'referral',
            'campaign' => 'contractor-alumni',
            'status' => 'converted',
        ]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create(['status' => 'paid', 'currency' => 'QAR', 'total' => 1200]);
    PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->for($invoice)
        ->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 1200]);

    return [$team, $certificate];
}
