<?php

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard metrics are scoped to the current team', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Lead::factory()->for($team)->create(['status' => 'new', 'source' => 'website', 'campaign' => 'kahramaa']);
    Lead::factory()->for($team)->create(['status' => 'converted', 'source' => 'website', 'campaign' => 'kahramaa']);
    Lead::factory()->for($otherTeam)->create(['status' => 'new']);
    Enrollment::factory()->for($team)->create(['status' => 'active', 'payment_status' => 'paid']);
    Enrollment::factory()->for($team)->create(['status' => 'pending', 'payment_status' => 'pending']);
    Enrollment::factory()->for($otherTeam)->create(['status' => 'active', 'payment_status' => 'paid']);
    PaymentTransaction::factory()->for($team)->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 1200]);
    PaymentTransaction::factory()->for($team)->create(['status' => 'pending', 'currency' => 'QAR', 'amount' => 500]);
    PaymentTransaction::factory()->for($otherTeam)->create(['status' => 'approved', 'currency' => 'QAR', 'amount' => 9000]);
    AttendanceRecord::factory()->for($team)->create(['status' => 'present']);
    AttendanceRecord::factory()->for($team)->create(['status' => 'absent']);
    ExamAttempt::factory()->for($team)->create(['result' => 'passed', 'submitted_at' => now()]);
    ExamAttempt::factory()->for($team)->create(['result' => 'failed', 'submitted_at' => now()]);
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
            ->where('metrics.learning.certificates_issued', 1)
            ->where('metrics.growth.renewal_opportunities', 1)
            ->where('metrics.growth.overdue_renewals', 0)
            ->where('metrics.growth.campaigns_tracked', 1));
});
