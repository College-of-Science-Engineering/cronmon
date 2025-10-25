<?php

use App\Livewire\Teams\Show;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('displays team details', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'name' => 'Engineering Team',
        'slug' => 'engineering-team',
    ]);
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertSeeLivewire(Show::class)
        ->assertSee('Engineering Team')
        ->assertSee('engineering-team')
        ->assertSee('Members') // Section heading
        ->assertSee('Scheduled Tasks') // Section heading
        ->assertSee('Back to Teams'); // Navigation button
});

it('displays list of team members', function () {
    // Arrange
    $user = User::factory()->create([
        'forenames' => 'John',
        'surname' => 'Doe',
        'email' => 'john@example.com',
    ]);
    $otherUser = User::factory()->create([
        'forenames' => 'Jane',
        'surname' => 'Smith',
        'email' => 'jane@example.com',
    ]);

    $team = Team::factory()->create();
    $team->users()->attach([$user->id, $otherUser->id]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->assertSee('Jane Smith')
        ->assertSee('jane@example.com');
});

it('displays list of team tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Database Backup',
        'status' => 'ok',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertSee('Database Backup')
        ->assertSee('ok');
});

it('shows empty state when team has no tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertSee('No tasks yet');
});

it('allows removing a team member', function () {
    // Arrange
    $user = User::factory()->create();
    $memberToRemove = User::factory()->create();

    $team = Team::factory()->create();
    $team->users()->attach([$user->id, $memberToRemove->id]);

    // Act
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->call('removeMember', $memberToRemove->id);

    // Assert
    expect($team->users()->where('users.id', $memberToRemove->id)->exists())->toBeFalse();
});

it('prevents removing the last member from a team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->call('removeMember', $user->id)
        ->assertHasErrors('member');

    expect($team->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

it('allows adding a new team member by email', function () {
    // Arrange
    $user = User::factory()->create();
    $newMember = User::factory()->create(['email' => 'newuser@example.com']);

    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->set('newMemberEmail', 'newuser@example.com')
        ->call('addMember');

    // Assert
    expect($team->users()->where('users.id', $newMember->id)->exists())->toBeTrue();
});

it('shows error when trying to add non-existent user', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->set('newMemberEmail', 'nonexistent@example.com')
        ->call('addMember')
        ->assertHasErrors('newMemberEmail');

    expect($team->users()->count())->toBe(1);
});

it('prevents adding a user who is already a member', function () {
    // Arrange
    $user = User::factory()->create(['email' => 'user@example.com']);
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->set('newMemberEmail', 'user@example.com')
        ->call('addMember')
        ->assertHasErrors('newMemberEmail');

    expect($team->users()->count())->toBe(1);
});

// Note: Edit functionality removed - teams are not editable in current version

it('shows delete team button for non-personal teams', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'johndoe']);
    $team = Team::factory()->create(['name' => 'Engineering']);
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertSee('Delete Team');
});

it('hides delete team button for personal teams', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'johndoe']);
    $personalTeam = Team::factory()->create(['name' => 'johndoe']);
    $personalTeam->users()->attach($user);

    // Act & Assert
    $response = $this->actingAs($user)
        ->get("/teams/{$personalTeam->id}");

    // Verify the Personal badge is shown (confirming it's recognized as personal)
    $response->assertSee('Personal');

    // Verify the Delete Team button is not rendered
    $response->assertDontSee('wire:click="deleteTeam"', false);
});

it('can delete team without tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->call('deleteTeam');

    // Assert
    expect(Team::find($team->id))->toBeNull();
});

it('shows migration modal when deleting team with tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    $this->actingAs($user);
    livewire(Show::class, ['team' => $team])
        ->call('deleteTeam')
        ->assertSet('showMigrationModal', true);

    expect(Team::find($team->id))->not->toBeNull();
});

it('can migrate tasks to another team and delete', function () {
    // Arrange
    $user = User::factory()->create();
    $sourceTeam = Team::factory()->create();
    $targetTeam = Team::factory()->create();
    $sourceTeam->users()->attach($user);
    $targetTeam->users()->attach($user);

    $task = ScheduledTask::factory()->create(['team_id' => $sourceTeam->id]);

    // Act
    $this->actingAs($user);
    livewire(Show::class, ['team' => $sourceTeam])
        ->set('migrationTargetTeamId', $targetTeam->id)
        ->call('confirmMigration');

    // Assert
    expect(Team::find($sourceTeam->id))->toBeNull();
    expect(ScheduledTask::find($task->id)->team_id)->toBe($targetTeam->id);
});

// Note: Authentication tests skipped - SSO not yet implemented

it('prevents viewing team user is not a member of', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();

    // Act & Assert
    $this->actingAs($user)
        ->get("/teams/{$team->id}")
        ->assertForbidden();
});
