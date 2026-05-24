<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;

test('team policy authorizes actions based on membership roles', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $policy = new TeamPolicy;

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->view($owner, $team))->toBeTrue()
        ->and($policy->view($outsider, $team))->toBeFalse()
        ->and($policy->update($owner, $team))->toBeTrue()
        ->and($policy->update($admin, $team))->toBeTrue()
        ->and($policy->update($member, $team))->toBeFalse()
        ->and($policy->addMember($owner, $team))->toBeTrue()
        ->and($policy->addMember($admin, $team))->toBeFalse()
        ->and($policy->updateMember($owner, $team))->toBeTrue()
        ->and($policy->updateMember($admin, $team))->toBeFalse()
        ->and($policy->removeMember($owner, $team))->toBeTrue()
        ->and($policy->removeMember($admin, $team))->toBeFalse()
        ->and($policy->inviteMember($owner, $team))->toBeTrue()
        ->and($policy->inviteMember($admin, $team))->toBeTrue()
        ->and($policy->inviteMember($member, $team))->toBeFalse()
        ->and($policy->cancelInvitation($owner, $team))->toBeTrue()
        ->and($policy->cancelInvitation($admin, $team))->toBeTrue()
        ->and($policy->cancelInvitation($member, $team))->toBeFalse()
        ->and($policy->delete($owner, $team))->toBeTrue()
        ->and($policy->delete($admin, $team))->toBeFalse()
        ->and($policy->delete($owner, $owner->personalTeam()))->toBeFalse();
});
