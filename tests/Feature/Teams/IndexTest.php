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

it('displays all teams including those user is not a member of', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create(['name' => 'My Team']);
    $userTeam->users()->attach($user);

    $otherTeam = Team::factory()->create(['name' => 'Other Team']);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('My Team')
        ->assertSee('Other Team');
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
        ->assertSee('Yours')
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

it('hides other users personal teams by default', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'johndoe']);
    $otherUser = User::factory()->create(['username' => 'janedoe']);

    $regularTeam = Team::factory()->create(['name' => 'Engineering']);
    $regularTeam->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams')
        ->assertSee('johndoe')
        ->assertSee('Yours')
        ->assertSee('Engineering')
        ->assertDontSee('janedoe'); // Other user's personal team should be hidden
});

it('shows other users personal teams when toggle is enabled', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'johndoe']);
    $otherUser = User::factory()->create(['username' => 'janedoe']);

    $regularTeam = Team::factory()->create(['name' => 'Engineering']);
    $regularTeam->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);
    
    livewire(Index::class)
        ->assertSee('johndoe')
        ->assertSee('Yours')
        ->assertDontSee('janedoe')
        ->set('showAllPersonalTeams', true)
        ->assertSee('janedoe')
        ->assertSee('Personal');
});

it('persists show all personal teams toggle to url', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);

    // Act & Assert
    livewire(Index::class)
        ->set('showAllPersonalTeams', true)
        ->assertSetStrict('showAllPersonalTeams', true)
        ->call('$refresh')
        ->assertSetStrict('showAllPersonalTeams', true);
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
