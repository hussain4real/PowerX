<?php

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\ValidTeamInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Validator;

test('team slugs ignore non numeric suffixes when choosing the next suffix', function () {
    Team::factory()->create(['name' => 'Acme Draft', 'slug' => 'acme-draft']);

    $team = Team::create(['name' => 'Acme']);

    expect($team->slug)->toBe('acme-1');
});

test('users can query their owned teams', function () {
    $user = User::factory()->create();
    $ownedTeam = Team::factory()->create();
    $adminTeam = Team::factory()->create();

    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $adminTeam->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->ownedTeams()->pluck('teams.id')->all())
        ->toContain($ownedTeam->id)
        ->not->toContain($adminTeam->id);
});

test('users cannot switch to unrelated teams', function () {
    $user = User::factory()->create();
    $currentTeamId = $user->current_team_id;
    $unrelatedTeam = Team::factory()->create();

    expect($user->switchTeam($unrelatedTeam))->toBeFalse()
        ->and($user->fresh()->current_team_id)->toBe($currentTeamId);
});

test('users can determine if they own a team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    expect($owner->ownsTeam($team))->toBeTrue()
        ->and($member->ownsTeam($team))->toBeFalse();
});

test('memberships expose their related team and user with cast roles', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $membership = Membership::query()
        ->where('team_id', $team->id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    expect($membership->role)->toBe(TeamRole::Admin)
        ->and($membership->team->is($team))->toBeTrue()
        ->and($membership->user->is($user))->toBeTrue();
});

test('team invitations expose inviter relationship and pending status', function () {
    $inviter = User::factory()->create();
    $invitation = TeamInvitation::factory()->create([
        'invited_by' => $inviter->id,
    ]);

    expect($invitation->inviter->is($inviter))->toBeTrue()
        ->and($invitation->isPending())->toBeTrue();
});

test('valid team invitation rule rejects invalid values', function () {
    $validator = Validator::make([
        'invitation' => 'not-an-invitation',
    ], [
        'invitation' => [new ValidTeamInvitation(User::factory()->create())],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('invitation'))->toBe(__('This invitation was sent to a different email address.'));
});

test('valid team invitation rule rejects accepted invitations', function () {
    $user = User::factory()->create(['email' => 'invited@example.com']);
    $invitation = TeamInvitation::factory()->accepted()->create([
        'email' => 'invited@example.com',
    ]);

    $validator = Validator::make([
        'invitation' => $invitation,
    ], [
        'invitation' => [new ValidTeamInvitation($user)],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('invitation'))->toBe(__('This invitation has already been accepted.'));
});

test('team invitation notifications include mail and array representations', function () {
    $inviter = User::factory()->create(['name' => 'Taylor Otwell']);
    $team = Team::factory()->create(['name' => 'Laravel Core']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Admin,
        'invited_by' => $inviter->id,
    ]);

    $notification = new TeamInvitationNotification($invitation);
    $mail = $notification->toMail((object) []);

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->via((object) []))->toBe(['mail'])
        ->and($mail->subject)->toBe("You've been invited to join Laravel Core")
        ->and($mail->introLines)->toContain('Taylor Otwell has invited you to join the Laravel Core team.')
        ->and($mail->actionText)->toBe('Accept invitation')
        ->and($mail->actionUrl)->toBe(url("/invitations/{$invitation->code}/accept"))
        ->and($notification->toArray((object) []))->toBe([
            'invitation_id' => $invitation->id,
            'team_id' => $team->id,
            'team_name' => 'Laravel Core',
            'role' => TeamRole::Admin->value,
        ]);
});

test('team roles expose hierarchy comparisons', function () {
    expect(TeamRole::Owner->level())->toBe(3)
        ->and(TeamRole::Admin->level())->toBe(2)
        ->and(TeamRole::Member->level())->toBe(1)
        ->and(TeamRole::Owner->isAtLeast(TeamRole::Admin))->toBeTrue()
        ->and(TeamRole::Admin->isAtLeast(TeamRole::Member))->toBeTrue()
        ->and(TeamRole::Member->isAtLeast(TeamRole::Admin))->toBeFalse();
});
