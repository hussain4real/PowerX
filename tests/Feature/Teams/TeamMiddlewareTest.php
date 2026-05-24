<?php

use App\Enums\TeamRole;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('dashboard route switches the authenticated user to the route team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Second Team']);
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk();

    expect($user->fresh()->isCurrentTeam($team))->toBeTrue();
});

test('team middleware enforces minimum roles', function () {
    Route::get('/coverage/admin-team/{team}', fn () => response('ok'))
        ->middleware(['web', 'auth', EnsureTeamMembership::class.':admin'])
        ->name('coverage.admin-team');

    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($admin)
        ->get("/coverage/admin-team/{$team->slug}")
        ->assertOk();

    $this
        ->actingAs($member)
        ->get("/coverage/admin-team/{$team->slug}")
        ->assertForbidden();
});

test('team middleware rejects unknown minimum roles', function () {
    Route::get('/coverage/invalid-team-role/{team}', fn () => response('ok'))
        ->middleware(['web', 'auth', EnsureTeamMembership::class.':manager'])
        ->name('coverage.invalid-team-role');

    $owner = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($owner)
        ->get("/coverage/invalid-team-role/{$team->slug}")
        ->assertForbidden();
});
