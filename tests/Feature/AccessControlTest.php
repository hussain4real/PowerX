<?php

use App\Enums\PowerXPermission;
use App\Enums\PowerXRole;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Filament\Panel;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('powerx roles and permissions are seeded', function () {
    expect(Role::query()->count())->toBe(count(PowerXRole::cases()))
        ->and(Permission::query()->count())->toBe(count(PowerXPermission::cases()))
        ->and(Role::findByName(PowerXRole::Management->value)->hasPermissionTo(PowerXPermission::ManageSettings->value))->toBeTrue()
        ->and(Role::findByName(PowerXRole::Management->value)->hasPermissionTo(PowerXPermission::ViewReports->value))->toBeTrue()
        ->and(Role::findByName(PowerXRole::Sales->value)->hasPermissionTo(PowerXPermission::ViewReports->value))->toBeFalse()
        ->and(Role::findByName(PowerXRole::Finance->value)->hasPermissionTo(PowerXPermission::ViewReports->value))->toBeFalse()
        ->and(Role::findByName(PowerXRole::Instructor->value)->hasPermissionTo(PowerXPermission::ViewReports->value))->toBeFalse()
        ->and(Role::findByName(PowerXRole::Student->value)->permissions)->toHaveCount(0);
});

test('role access helpers expose the expected workspace boundaries', function (PowerXRole $role, array $expected) {
    $user = grantPowerXRole(User::factory()->create(), $role);

    expect($user->canViewOperationsDashboard())->toBe($expected['dashboard'])
        ->and($user->canViewInstructorPortal())->toBe($expected['instructor'])
        ->and($user->canViewStudentPortal($user->currentTeam))->toBe($expected['student'])
        ->and($user->canViewCorporatePortal())->toBe($expected['corporate'])
        ->and($user->canManagePowerXTeams())->toBe($expected['teams']);
})->with([
    'management' => [PowerXRole::Management, ['dashboard' => true, 'instructor' => false, 'student' => false, 'corporate' => false, 'teams' => true]],
    'admin' => [PowerXRole::Admin, ['dashboard' => true, 'instructor' => false, 'student' => false, 'corporate' => false, 'teams' => true]],
    'sales' => [PowerXRole::Sales, ['dashboard' => false, 'instructor' => false, 'student' => false, 'corporate' => false, 'teams' => false]],
    'finance' => [PowerXRole::Finance, ['dashboard' => false, 'instructor' => false, 'student' => false, 'corporate' => false, 'teams' => false]],
    'instructor' => [PowerXRole::Instructor, ['dashboard' => false, 'instructor' => true, 'student' => false, 'corporate' => false, 'teams' => false]],
    'student' => [PowerXRole::Student, ['dashboard' => false, 'instructor' => false, 'student' => true, 'corporate' => false, 'teams' => false]],
    'corporate' => [PowerXRole::Corporate, ['dashboard' => false, 'instructor' => false, 'student' => false, 'corporate' => true, 'teams' => false]],
    'support' => [PowerXRole::Support, ['dashboard' => false, 'instructor' => false, 'student' => false, 'corporate' => false, 'teams' => false]],
]);

test('inertia shared access flags are role aware', function () {
    $manager = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $student = grantPowerXRole(User::factory()->create(), PowerXRole::Student);

    $this
        ->actingAs($manager)
        ->get(route('dashboard', ['current_team' => $manager->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.viewOperationsDashboard', true)
            ->where('can.viewAdminPanel', true)
            ->where('can.manageTeams', true)
            ->where('can.createTeams', true)
            ->where('can.viewInstructorPortal', false)
            ->where('can.viewStudentPortal', false));

    $this
        ->actingAs($student)
        ->get(route('student.portal', ['current_team' => $student->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.viewOperationsDashboard', false)
            ->where('can.viewAdminPanel', false)
            ->where('can.manageTeams', false)
            ->where('can.createTeams', false)
            ->where('can.viewStudentPortal', true)
            ->where('teams', []));
});

test('corporate and student portals are mutually isolated', function () {
    $corporate = grantPowerXRole(User::factory()->create(), PowerXRole::Corporate);
    $student = grantPowerXRole(User::factory()->create(), PowerXRole::Student);

    $this
        ->actingAs($corporate)
        ->get(route('corporate.portal', ['current_team' => $corporate->currentTeam]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Corporate/Portal'));

    $this
        ->actingAs($corporate)
        ->get(route('student.portal', ['current_team' => $corporate->currentTeam]))
        ->assertForbidden();

    $this
        ->actingAs($student)
        ->get(route('corporate.portal', ['current_team' => $student->currentTeam]))
        ->assertForbidden();
});

test('a staff user with a linked student profile can access only their own student portal', function () {
    $staffStudent = grantPowerXRole(User::factory()->create(), PowerXRole::Support);

    StudentProfile::factory()
        ->for($staffStudent)
        ->for($staffStudent->currentTeam)
        ->create();

    expect($staffStudent->canViewStudentPortal($staffStudent->currentTeam))->toBeTrue();

    $this
        ->actingAs($staffStudent)
        ->get(route('student.portal', ['current_team' => $staffStudent->currentTeam]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Student/Portal'));
});

test('only authorized staff can access the filament admin panel', function () {
    $manager = User::factory()->create();
    $student = User::factory()->create();

    $manager->assignRole(PowerXRole::Management->value);
    $student->assignRole(PowerXRole::Student->value);

    $adminPanel = Panel::make()->id('admin');

    expect($manager->canAccessPanel($adminPanel))->toBeTrue()
        ->and($student->canAccessPanel($adminPanel))->toBeFalse()
        ->and($manager->canAccessPanel(Panel::make()->id('student')))->toBeFalse();
});

test('guests are redirected to the filament login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
