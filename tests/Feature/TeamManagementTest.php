<?php

use App\Livewire\Teams\Show as TeamShow;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('does not delete a personal team', function () {
    $user = User::factory()->create([
        'username' => 'jdoe',
    ]);

    $team = $user->personalTeam(); // Use auto-created personal team

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->call('deleteTeam')
        ->assertSet('showMigrationModal', false);

    expect(Team::query()->whereKey($team->id)->exists())->toBeTrue();
});

it('prompts for migration when team has tasks', function () {
    $user = User::factory()->create();

    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->call('deleteTeam')
        ->assertSet('showMigrationModal', true);

    expect(Team::query()->whereKey($team->id)->exists())->toBeTrue();
});

it('deletes an empty non personal team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->call('deleteTeam')
        ->assertRedirect(route('teams.index'));

    expect(Team::query()->whereKey($team->id)->exists())->toBeFalse();
});

it('migrates tasks before deleting a team', function () {
    $user = User::factory()->create();

    $teamToDelete = Team::factory()->create();
    $teamToDelete->users()->attach($user);

    $targetTeam = Team::factory()->create();
    $targetTeam->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $teamToDelete->id,
    ]);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $teamToDelete])
        ->set('showMigrationModal', true)
        ->set('migrationTargetTeamId', $targetTeam->id)
        ->call('confirmMigration')
        ->assertRedirect(route('teams.index'));

    expect(Team::query()->whereKey($teamToDelete->id)->exists())->toBeFalse();
    expect($task->fresh()->team_id)->toBe($targetTeam->id);
});

it('adds a new team member', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $newMember = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->set('newMemberEmail', $newMember->email)
        ->call('addMember')
        ->assertDispatched('member-added')
        ->assertSet('newMemberEmail', '');

    expect($team->fresh()->users()->whereKey($newMember->id)->exists())->toBeTrue();
});

it('prevents adding duplicate members', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->set('newMemberEmail', $user->email)
        ->call('addMember')
        ->assertHasErrors(['newMemberEmail']);

    expect($team->fresh()->users()->count())->toBe(1);
});

it('prevents removing the final team member', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->call('removeMember', $user->id)
        ->assertHasErrors(['member']);

    expect($team->fresh()->users()->count())->toBe(1);
});

it('removes a team member when more than one exists', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $otherUser = User::factory()->create();
    $team->users()->attach($otherUser);

    $this->actingAs($user);

    Livewire::test(TeamShow::class, ['team' => $team])
        ->call('removeMember', $otherUser->id)
        ->assertDispatched('member-removed');

    expect($team->fresh()->users()->whereKey($otherUser->id)->exists())->toBeFalse();
});
