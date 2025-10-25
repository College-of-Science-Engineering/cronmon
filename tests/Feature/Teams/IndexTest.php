<?php

use App\Livewire\Teams\Index;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('displays teams user is a member of', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Engineering Team']);
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSeeLivewire(Index::class)
        ->assertSee('Engineering Team');
});

it('does not display teams user is not a member of', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create(['name' => 'My Team']);
    $userTeam->users()->attach($user);

    $otherTeam = Team::factory()->create(['name' => 'Secret Team']);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('My Team')
        ->assertDontSee('Secret Team');
});

it('displays member count for each team', function () {
    // Arrange
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $team = Team::factory()->create();
    $team->users()->attach([$user->id, $otherUser->id]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('2'); // member count
});

it('displays task count for each team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->count(3)->create(['team_id' => $team->id]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('3'); // task count
});

it('always displays at least personal team for new users', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'alice']);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('alice')
        ->assertSee('Personal')
        ->assertDontSee('No teams yet');
});

it('shows create team button', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('Create Team');
});

it('indicates which teams are personal teams', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'johndoe']);
    $personalTeam = Team::factory()->create(['name' => 'johndoe']);
    $personalTeam->users()->attach($user);

    $regularTeam = Team::factory()->create(['name' => 'Engineering']);
    $regularTeam->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('Personal'); // badge or indicator
});

// Note: Authentication tests skipped - SSO not yet implemented

it('links to team detail page', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee(route('teams.show', $team));
});

it('links to create team page', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee(route('teams.create'));
});
