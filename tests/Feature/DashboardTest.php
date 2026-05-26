<?php

use App\Actions\PowerX\BuildCampaignAttributionMetrics;
use App\Actions\PowerX\BuildOperationsDashboard;
use App\Enums\PowerXRole;
use App\Http\Controllers\DashboardController;
use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard metrics are scoped to the current team', function () {
    $this->withoutVite();

    $user = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $company = Company::factory()->for($team)->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($company)
        ->create(['email' => 'converted@example.test', 'mobile' => '+974 5011 2233']);
    $attributedEnrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($course)
        ->for($profile, 'studentProfile')
        ->create(['status' => 'active', 'payment_status' => 'paid']);

    Lead::factory()
        ->for($team)
        ->for($company)
        ->for($course)
        ->create([
            'status' => 'new',
            'source' => 'website',
            'campaign' => 'kahramaa',
            'metadata' => ['campaign_cost' => 300, 'campaign_cost_currency' => 'QAR'],
        ]);
    Lead::factory()
        ->for($team)
        ->for($company)
        ->for($course)
        ->create([
            'email' => 'converted@example.test',
            'phone' => '+974 5011 2233',
            'status' => 'converted',
            'source' => 'website',
            'campaign' => 'kahramaa',
        ]);
    Lead::factory()->for($otherTeam)->create(['status' => 'new']);
    Enrollment::factory()->for($team)->create(['status' => 'pending', 'payment_status' => 'pending']);
    Enrollment::factory()->for($otherTeam)->create(['status' => 'active', 'payment_status' => 'paid']);
    PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($attributedEnrollment)
        ->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 1200]);
    PaymentTransaction::factory()->for($team)->create(['status' => 'pending', 'currency' => 'QAR', 'amount' => 500]);
    PaymentTransaction::factory()->for($otherTeam)->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 9000]);
    AttendanceRecord::factory()->for($team)->create(['status' => 'present']);
    AttendanceRecord::factory()->for($team)->create(['status' => 'absent']);
    ExamAttempt::factory()->for($team)->create(['result' => 'passed', 'score' => 80, 'submitted_at' => now(), 'metadata' => ['weak_topic' => 'Switchgear safety']]);
    ExamAttempt::factory()->for($team)->create(['result' => 'failed', 'score' => 60, 'submitted_at' => now(), 'metadata' => ['weak_topic' => 'Switchgear safety']]);
    Certificate::factory()->for($team)->create(['status' => 'issued', 'issued_at' => now(), 'expires_at' => now()->addDays(30)]);
    Certificate::factory()->for($team)->create(['status' => 'draft', 'issued_at' => null]);

    $this->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('metrics.leads.total', 2)
            ->where('metrics.leads.new', 1)
            ->where('metrics.leads.converted', 1)
            ->where('metrics.enrollments.active', 1)
            ->where('metrics.enrollments.pending', 1)
            ->where('metrics.finance.pending_payments', 1)
            ->where('metrics.finance.approved_revenue_label', 'QAR 1,200')
            ->where('metrics.learning.attendance_rate', 50)
            ->where('metrics.learning.exam_pass_rate', 50)
            ->where('metrics.learning.average_exam_score', 70)
            ->where('metrics.learning.top_weak_topic', 'Switchgear safety')
            ->where('metrics.learning.top_weak_topic_count', 2)
            ->where('metrics.learning.certificates_issued', 1)
            ->where('metrics.growth.renewal_opportunities', 1)
            ->where('metrics.growth.overdue_renewals', 0)
            ->where('metrics.growth.campaigns_tracked', 1)
            ->where('metrics.growth.campaign_revenue_label', 'QAR 1,200.00')
            ->where('metrics.growth.campaign_cost_label', 'QAR 300.00')
            ->where('metrics.growth.campaign_roi_label', '300.0%')
            ->where('metrics.growth.campaign_roi_leader', 'website / kahramaa')
            ->where('metrics.growth.tracking_status', 'Blocked - internal CRM/finance attribution only'));
});

test('campaign attribution can use payment metadata and non qar roi currencies', function (): void {
    $manager = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $team = $manager->currentTeam;

    Lead::factory()
        ->for($team)
        ->create([
            'source' => 'partner',
            'campaign' => 'USD Push',
            'status' => 'qualified',
            'metadata' => [
                'campaign' => [
                    'cost' => 100,
                    'currency' => 'usd',
                ],
            ],
        ]);

    PaymentTransaction::factory()
        ->for($team)
        ->create([
            'enrollment_id' => null,
            'invoice_id' => null,
            'company_id' => null,
            'student_profile_id' => null,
            'approved_by_id' => null,
            'status' => 'approved',
            'currency' => 'usd',
            'amount' => 250,
            'metadata' => [
                'attribution' => [
                    'source' => 'partner',
                    'campaign' => 'USD Push',
                ],
            ],
        ]);

    $campaign = app(BuildCampaignAttributionMetrics::class)->handle($team)->first();

    expect($campaign)->toMatchArray([
        'source' => 'partner',
        'campaign' => 'USD Push',
        'costLabel' => 'USD 100.00',
        'revenueLabel' => 'USD 250.00',
        'roi' => 150.0,
        'roiLabel' => '150.0%',
    ]);
});

test('non reporting personas are redirected to their expected workspace surface', function (PowerXRole $role, string $expectedRoute) {
    $user = grantPowerXRole(User::factory()->create(), $role);
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team]))
        ->assertRedirect(route($expectedRoute, ['current_team' => $team]));
})->with([
    'student' => [PowerXRole::Student, 'student.portal'],
    'instructor' => [PowerXRole::Instructor, 'instructor.portal'],
    'corporate' => [PowerXRole::Corporate, 'corporate.portal'],
]);

test('staff without operations dashboard access are redirected to the admin panel', function (PowerXRole $role) {
    $user = grantPowerXRole(User::factory()->create(), $role);
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team]))
        ->assertRedirect('/admin');
})->with([
    'sales' => PowerXRole::Sales,
    'finance' => PowerXRole::Finance,
    'support' => PowerXRole::Support,
]);

test('users without a powerx destination cannot open the operations dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team]))
        ->assertForbidden();
});

test('dashboard controller rejects authenticated users without a current team', function () {
    $user = User::factory()->create(['current_team_id' => null]);
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn (): User => $user);

    expect(fn () => app(DashboardController::class)(
        $request,
        app(BuildOperationsDashboard::class),
    ))->toThrow(HttpException::class);
});

test('only management style roles can export operational reports', function () {
    $manager = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $student = grantPowerXRole(User::factory()->create(), PowerXRole::Student);

    $this
        ->actingAs($manager)
        ->get(route('reports.operational.csv', ['current_team' => $manager->currentTeam]))
        ->assertSuccessful();

    $this
        ->actingAs($student)
        ->get(route('reports.operational.csv', ['current_team' => $student->currentTeam]))
        ->assertForbidden();
});
