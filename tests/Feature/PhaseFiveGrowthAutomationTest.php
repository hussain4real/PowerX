<?php

use App\Actions\PowerX\BuildCampaignAttributionMetrics;
use App\Actions\PowerX\BuildOperationalReport;
use App\Actions\PowerX\BuildOperationsDashboard;
use App\Actions\PowerX\CreateRenewalCampaignCommunications;
use App\Ai\Agents\PowerXCourseGuide;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

test('assistant feature flag controls public endpoint and page props', function (): void {
    $this->withoutVite();

    $course = Course::factory()->create([
        'title' => 'Kahramaa Approval Preparation',
        'slug' => 'kahramaa-approval-preparation',
        'status' => 'published',
        'published_at' => now(),
    ]);

    powerxDisableAssistant();

    $this
        ->postJson(route('powerx-assistant.store'), ['message' => 'Which course should I take?'])
        ->assertNotFound();

    $this
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('aiAssistant.enabled', false)
            ->where('aiAssistant.endpoint', null));

    powerxEnableAssistant();

    $this
        ->get(route('courses.show', ['course' => $course]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('aiAssistant.enabled', true)
            ->where('aiAssistant.source', 'ai_chat')
            ->where('aiAssistant.courseId', $course->id)
            ->where('aiAssistant.courseTitle', $course->title));
});

test('assistant answers from approved course data and creates or updates CRM handoffs', function (): void {
    powerxEnableAssistant();

    $course = Course::factory()->create([
        'title' => 'Kahramaa Exam Preparation',
        'category' => 'Kahramaa',
        'status' => 'published',
        'summary' => 'Structured preparation for Qatar electrical approval exams.',
        'delivery_mode' => 'blended',
        'currency' => 'QAR',
        'base_price' => 1200,
        'published_at' => now(),
    ]);
    CoursePackage::factory()->for($course)->create([
        'name' => 'Exam Ready',
        'currency' => 'QAR',
        'price' => 1500,
        'is_active' => true,
    ]);
    TrainingBatch::factory()->for($course)->for($course->team)->create([
        'name' => 'Weekend Batch 07',
        'status' => 'scheduled',
        'starts_at' => now()->addDays(12)->setTime(9, 0),
    ]);

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'I want to register for the Kahramaa course and ask about weekend schedule.',
            'name' => 'Maha Electrician',
            'email' => 'maha@example.com',
            'phone' => '+97450001122',
            'company_name' => 'Doha Electrical Works',
            'course_id' => $course->id,
            'utm_source' => 'linkedin',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'kahramaa-q3',
        ])
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('guardrailTriggered', false)
            ->where('handoffCreated', true)
            ->where('suggestedCourse.id', $course->id)
            ->where('suggestedCourse.title', $course->title)
            ->where('answer', fn (string $answer): bool => str_contains($answer, 'Kahramaa Exam Preparation') && str_contains($answer, 'QAR 1,200.00') && str_contains($answer, 'Weekend Batch 07'))
            ->etc());

    $lead = Lead::query()->firstOrFail();

    expect($lead->source)->toBe('ai_chat')
        ->and($lead->campaign)->toBe('kahramaa-q3')
        ->and($lead->status)->toBe(Lead::STATUS_QUALIFIED)
        ->and($lead->company)->toBeInstanceOf(Company::class)
        ->and(data_get($lead->metadata, 'channel'))->toBe('ai_assistant')
        ->and(data_get($lead->metadata, 'channel_group'))->toBe('AI assistant')
        ->and(data_get($lead->metadata, 'attribution.utm_source'))->toBe('linkedin')
        ->and(data_get($lead->metadata, 'ai_assistant.handoff_summary'))->toContain('registration_help');

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'Can companies train several employees at once?',
            'email' => 'maha@example.com',
            'phone' => '+97450001122',
            'course_id' => $course->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('handoffCreated', true);

    expect(Lead::query()->count())->toBe(1)
        ->and($lead->refresh()->notes)->toContain('several employees')
        ->and(data_get($lead->metadata, 'ai_assistant.last_answer'))->toContain('Corporate requests');

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'Kahramaa Exam Preparation',
        ])
        ->assertSuccessful()
        ->assertJsonPath('suggestedCourse.id', $course->id);
});

test('assistant blocks unapproved claims and can hand off guardrail conversations', function (): void {
    powerxEnableAssistant();

    $course = Course::factory()->create([
        'title' => 'Industrial Safety Refresher',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'Do you guarantee certificate guarantee and government approval?',
            'name' => 'Noura Coordinator',
            'email' => 'noura@example.com',
            'course_id' => $course->id,
            'referral_name' => 'Aisha Alumni',
            'referral_phone' => '+97450111111',
        ])
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('guardrailTriggered', true)
            ->where('handoffCreated', true)
            ->where('answer', config('powerx_growth.ai_assistant.approved_fallback'))
            ->etc());

    $lead = Lead::query()->firstOrFail();

    expect(data_get($lead->metadata, 'ai_assistant.guardrail_triggered'))->toBeTrue()
        ->and(data_get($lead->metadata, 'channel_group'))->toBe('Referral')
        ->and(data_get($lead->metadata, 'referral.name'))->toBe('Aisha Alumni');

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'What courses are available?',
        ])
        ->assertSuccessful()
        ->assertJsonPath('handoffCreated', false);
});

test('assistant uses approved catalog fallback when no courses are published', function (): void {
    powerxEnableAssistant();

    $this
        ->postJson(route('powerx-assistant.store'), [
            'source' => 'ai_chat',
            'message' => 'What courses are available?',
        ])
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('handoffCreated', false)
            ->where('suggestedCourse', null)
            ->where('answer', 'PowerX can share approved course information once the course catalog is configured.')
            ->etc());
});

test('assistant handoffs classify paid direct and website channel groups', function (): void {
    powerxEnableAssistant();

    Course::factory()->create([
        'title' => 'Electrical Technician Fundamentals',
        'status' => 'published',
        'published_at' => now(),
    ]);

    foreach ([
        ['source' => 'paid_ads', 'email' => 'paid@example.com', 'expected' => 'Paid / social'],
        ['source' => 'phone', 'email' => 'phone@example.com', 'expected' => 'Direct'],
        ['source' => 'website', 'email' => 'web@example.com', 'expected' => 'Website'],
    ] as $case) {
        $this
            ->postJson(route('powerx-assistant.store'), [
                'source' => $case['source'],
                'message' => 'Please call me about registration.',
                'name' => $case['source'].' lead',
                'email' => $case['email'],
            ])
            ->assertSuccessful();

        expect(data_get(Lead::query()->where('email', $case['email'])->firstOrFail()->metadata, 'channel_group'))
            ->toBe($case['expected']);
    }
});

test('public inquiry and registration preserve referral attribution', function (): void {
    $course = Course::factory()->create([
        'title' => 'Power Distribution Systems',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $package = CoursePackage::factory()->for($course)->create([
        'name' => 'Professional',
        'is_active' => true,
    ]);

    $this
        ->post(route('leads.store'), [
            'name' => 'Aamir Khan',
            'email' => 'aamir@example.com',
            'course_id' => $course->id,
            'message' => 'Alumni suggested this course.',
            'referral_name' => 'Fatima Alumni',
            'referral_email' => 'fatima@example.com',
            'referral_relationship' => 'Former student',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lead = Lead::query()->where('email', 'aamir@example.com')->firstOrFail();

    expect($lead->source)->toBe('referral')
        ->and(data_get($lead->metadata, 'channel_group'))->toBe('Referral')
        ->and(data_get($lead->metadata, 'referral.relationship'))->toBe('Former student');

    $this
        ->post(route('courses.registrations.store', ['course' => $course]), [
            'full_name' => 'Fatima Ali',
            'email' => 'fatima@example.net',
            'mobile' => '+97451111111',
            'course_package_id' => $package->id,
            'referral_name' => 'Aamir Khan',
            'referral_phone' => '+97450000000',
        ])
        ->assertRedirect(route('courses.show', ['course' => $course]))
        ->assertSessionHasNoErrors();

    $profile = StudentProfile::query()->where('email', 'fatima@example.net')->firstOrFail();
    $enrollment = Enrollment::query()->whereBelongsTo($profile, 'studentProfile')->firstOrFail();
    $registrationLead = Lead::query()->where('email', 'fatima@example.net')->firstOrFail();

    expect(data_get($profile->metadata, 'channel_group'))->toBe('Referral')
        ->and(data_get($enrollment->metadata, 'referral.name'))->toBe('Aamir Khan')
        ->and($registrationLead->source)->toBe('referral')
        ->and(data_get($registrationLead->metadata, 'referral.phone'))->toBe('+97450000000');

    $this
        ->post(route('leads.store'), [
            'name' => 'Direct Caller',
            'phone' => '+97459999999',
            'course_id' => $course->id,
            'source' => 'whatsapp',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this
        ->post(route('courses.registrations.store', ['course' => $course]), [
            'full_name' => 'Phone Lead',
            'email' => 'phone-lead@example.net',
            'mobile' => '+97452222222',
            'source' => 'phone',
        ])
        ->assertRedirect(route('courses.show', ['course' => $course]))
        ->assertSessionHasNoErrors();

    expect(data_get(Lead::query()->where('phone', '+97459999999')->firstOrFail()->metadata, 'channel_group'))->toBe('Direct')
        ->and(data_get(StudentProfile::query()->where('email', 'phone-lead@example.net')->firstOrFail()->metadata, 'channel_group'))->toBe('Direct');
});

test('campaign attribution reports channel group referral conversion cost revenue and roi', function (): void {
    $team = Team::factory()->create(['name' => 'PowerX Growth']);
    $course = Course::factory()->for($team)->create(['status' => 'published', 'published_at' => now()]);
    $company = Company::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->for($company)->create([
        'email' => 'converted@example.com',
        'mobile' => '+97455550000',
    ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => Enrollment::STATUS_ACTIVE, 'payment_status' => 'paid']);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create(['status' => 'paid', 'currency' => 'QAR', 'total' => 2000]);

    Lead::factory()
        ->for($team)
        ->for($company)
        ->for($course)
        ->create([
            'email' => 'converted@example.com',
            'phone' => '+97455550000',
            'source' => 'referral',
            'campaign' => 'alumni-push',
            'status' => Lead::STATUS_WON,
            'metadata' => [
                'referral' => ['name' => 'Aisha Alumni'],
                'campaign_cost' => 400,
                'campaign_cost_currency' => 'QAR',
            ],
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'source' => 'paid_ads',
            'campaign' => 'summer-cpc',
            'status' => Lead::STATUS_QUALIFIED,
            'metadata' => [
                'attribution' => ['utm_medium' => 'cpc'],
                'campaign_cost' => 150,
                'campaign_cost_currency' => 'QAR',
            ],
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'source' => 'ai_chat',
            'campaign' => 'assistant',
            'status' => Lead::STATUS_NEW,
            'metadata' => ['channel' => 'ai_assistant'],
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'source' => 'email',
            'campaign' => 'newsletter',
            'status' => Lead::STATUS_NEW,
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'source' => 'phone',
            'campaign' => 'call-drive',
            'status' => Lead::STATUS_NEW,
        ]);
    Lead::factory()
        ->for($team)
        ->for($course)
        ->create([
            'source' => 'website',
            'campaign' => 'custom-group',
            'status' => Lead::STATUS_NEW,
            'metadata' => ['channel_group' => 'Partner portal'],
        ]);
    PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->for($invoice)
        ->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 2000]);

    $campaigns = app(BuildCampaignAttributionMetrics::class)->handle($team);
    $referralCampaign = $campaigns->firstWhere('campaign', 'alumni-push');
    $paidCampaign = $campaigns->firstWhere('campaign', 'summer-cpc');
    $emailCampaign = $campaigns->firstWhere('campaign', 'newsletter');
    $directCampaign = $campaigns->firstWhere('campaign', 'call-drive');
    $customCampaign = $campaigns->firstWhere('campaign', 'custom-group');
    $dashboard = app(BuildOperationsDashboard::class)->handle($team);
    $report = app(BuildOperationalReport::class)->handle($team);
    $leadSource = collect($report['sections'])->firstWhere('key', 'lead_source');

    expect($referralCampaign)->toMatchArray([
        'source' => 'referral',
        'channelGroup' => 'Referral',
        'leadCount' => 1,
        'convertedCount' => 1,
        'enrollmentCount' => 1,
        'referralCount' => 1,
        'referralConvertedCount' => 1,
        'referralConversionRate' => '100.0%',
        'costLabel' => 'QAR 400.00',
        'revenueLabel' => 'QAR 2,000.00',
        'roiLabel' => '400.0%',
        'attributionStatus' => config('powerx_growth.campaigns.attribution_status'),
    ])
        ->and($paidCampaign['channelGroup'])->toBe('Paid / social')
        ->and($emailCampaign['channelGroup'])->toBe('Email')
        ->and($directCampaign['channelGroup'])->toBe('Direct')
        ->and($customCampaign['channelGroup'])->toBe('Partner portal')
        ->and($dashboard['growth']['referral_conversion_label'])->toBe('1 / 1 (100.0%)')
        ->and(collect($leadSource['rows'])->firstWhere('Campaign', 'alumni-push'))->toMatchArray([
            'Channel group' => 'Referral',
            'Enrollment count' => '1',
            'Referral count' => '1',
            'Referral conversion' => '100.0%',
            'Attribution' => config('powerx_growth.campaigns.attribution_status'),
        ]);
});

test('renewal campaign action schedules reportable reminders idempotently', function (): void {
    $team = Team::factory()->create(['name' => 'PowerX Renewals']);
    $course = Course::factory()->for($team)->create([
        'title' => 'Kahramaa Exam Preparation',
        'category' => 'Kahramaa',
        'status' => 'published',
        'published_at' => now(),
    ]);
    Course::factory()->for($team)->create([
        'title' => 'Advanced Power Distribution',
        'category' => 'Kahramaa',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $profile = StudentProfile::factory()->for($team)->create(['mobile' => '+97450112233']);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => Enrollment::STATUS_COMPLETED, 'payment_status' => 'paid']);
    $certificate = Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create([
            'certificate_number' => 'PX-CERT-GROWTH',
            'status' => 'issued',
            'expires_at' => now()->addDays(45),
        ]);
    Certificate::factory()->for($team)->create([
        'status' => 'draft',
        'expires_at' => now()->addDays(20),
    ]);

    $communications = app(CreateRenewalCampaignCommunications::class)->handle($team);
    $secondRun = app(CreateRenewalCampaignCommunications::class)->handle($team);

    expect($communications)->toHaveCount(1)
        ->and($secondRun)->toHaveCount(1)
        ->and(Communication::query()->where('template_key', 'renewal_reminder')->count())->toBe(1)
        ->and($communications->first()->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and($communications->first()->metadata)->toMatchArray([
            'certificate_id' => $certificate->id,
            'source' => config('powerx_growth.renewals.source'),
            'campaign' => config('powerx_growth.renewals.campaign'),
            'channel_group' => 'Renewal',
            'campaign_workflow' => 'certificate_renewal',
        ]);
});

test('course guide agent instructions enforce approved knowledge guardrails', function (): void {
    $agent = new PowerXCourseGuide([
        'fallback' => 'Approved fallback only.',
        'disclaimer' => 'Approved disclaimer.',
    ]);

    expect((string) $agent->instructions())->toContain('approved PowerX course and FAQ data')
        ->and((string) $agent->instructions())->toContain('Approved fallback only.')
        ->and(iterator_to_array($agent->messages()))->toBe([])
        ->and(iterator_to_array($agent->tools()))->toBe([]);
});

function powerxEnableAssistant(): void
{
    config(['powerx_growth.ai_assistant.enabled' => true]);
    Feature::purge(config('powerx_growth.ai_assistant.feature'));
    Feature::flushCache();
}

function powerxDisableAssistant(): void
{
    config(['powerx_growth.ai_assistant.enabled' => false]);
    Feature::purge(config('powerx_growth.ai_assistant.feature'));
    Feature::flushCache();
}
