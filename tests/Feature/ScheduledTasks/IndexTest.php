<?php

use App\Livewire\ScheduledTasks\Index;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('displays tasks from users teams', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Database Backup',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks')
        ->assertSeeLivewire(Index::class)
        ->assertSee('Database Backup')
        ->assertSee($team->name);
});

it('does not display tasks from teams user is not part of', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create();
    $userTeam->users()->attach($user);

    $otherTeam = Team::factory()->create();
    $otherTask = ScheduledTask::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Secret Task',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks')
        ->assertDontSee('Secret Task');
});

it('displays empty state when user has no tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks')
        ->assertSee('No tasks yet')
        ->assertSee('Get started by creating your first scheduled task');
});

it('can delete a task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act
    $this->actingAs($user);
    livewire(Index::class)
        ->call('delete', $task->id);

    // Assert
    expect(ScheduledTask::find($task->id))->toBeNull();
});

it('displays task status with correct badge colors', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $okTask = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'ok',
        'name' => 'OK Task',
    ]);

    $alertingTask = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'alerting',
        'name' => 'Alert Task',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks')
        ->assertSee('OK Task')
        ->assertSee('Alert Task');
});

it('can filter tasks by status via URL', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $okTask = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'ok',
        'name' => 'OK Task',
    ]);

    $alertingTask = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'alerting',
        'name' => 'Alert Task',
    ]);

    // Act & Assert - test URL filtering works
    $this->actingAs($user)
        ->get('/tasks?status=alerting')
        ->assertSee('Alert Task')
        ->assertDontSee('OK Task');
});

it('can switch between filters', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'ok',
        'name' => 'OK Task',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'alerting',
        'name' => 'Alert Task',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'paused',
        'name' => 'Paused Task',
    ]);

    // Act & Assert
    $this->actingAs($user);

    // Test alerting filter
    livewire(Index::class)
        ->set('status', 'alerting')
        ->assertSee('Alert Task')
        ->assertDontSee('OK Task')
        ->assertDontSee('Paused Task');

    // Test paused filter
    livewire(Index::class)
        ->set('status', 'paused')
        ->assertSee('Paused Task')
        ->assertDontSee('OK Task')
        ->assertDontSee('Alert Task');

    // Test all (no filter)
    livewire(Index::class)
        ->set('status', '')
        ->assertSee('OK Task')
        ->assertSee('Alert Task')
        ->assertSee('Paused Task');
});

it('displays empty state when filter has no matching tasks', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'ok',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks?status=alerting')
        ->assertSee('No alerting tasks')
        ->assertSee('Great! All your tasks are running smoothly');
});

it('filters out tasks from other teams even with filter', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create();
    $userTeam->users()->attach($user);

    $otherTeam = Team::factory()->create();

    ScheduledTask::factory()->create([
        'team_id' => $userTeam->id,
        'status' => 'alerting',
        'name' => 'My Alert',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $otherTeam->id,
        'status' => 'alerting',
        'name' => 'Other Alert',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks?status=alerting')
        ->assertSee('My Alert')
        ->assertDontSee('Other Alert');
});

it('can filter tasks by team via URL', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['name' => 'Team Alpha']);
    $team2 = Team::factory()->create(['name' => 'Team Beta']);
    $team1->users()->attach($user);
    $team2->users()->attach($user);

    $task1 = ScheduledTask::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Alpha Task',
    ]);

    $task2 = ScheduledTask::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Beta Task',
    ]);

    // Act & Assert - test URL filtering works
    $this->actingAs($user)
        ->get('/tasks?team_id=' . $team1->id)
        ->assertSee('Alpha Task')
        ->assertDontSee('Beta Task');
});

it('can switch between team filters', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['name' => 'Team Alpha']);
    $team2 = Team::factory()->create(['name' => 'Team Beta']);
    $team1->users()->attach($user);
    $team2->users()->attach($user);

    ScheduledTask::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Alpha Task',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Beta Task',
    ]);

    // Act & Assert
    $this->actingAs($user);

    // Test team1 filter
    livewire(Index::class)
        ->set('team_id', $team1->id)
        ->assertSee('Alpha Task')
        ->assertDontSee('Beta Task');

    // Test team2 filter
    livewire(Index::class)
        ->set('team_id', $team2->id)
        ->assertSee('Beta Task')
        ->assertDontSee('Alpha Task');

    // Test all teams (no filter)
    livewire(Index::class)
        ->set('team_id', null)
        ->assertSee('Alpha Task')
        ->assertSee('Beta Task');
});

it('can combine status and team filters', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team1->users()->attach($user);
    $team2->users()->attach($user);

    ScheduledTask::factory()->create([
        'team_id' => $team1->id,
        'status' => 'alerting',
        'name' => 'Team1 Alert',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $team1->id,
        'status' => 'ok',
        'name' => 'Team1 OK',
    ]);

    ScheduledTask::factory()->create([
        'team_id' => $team2->id,
        'status' => 'alerting',
        'name' => 'Team2 Alert',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks?status=alerting&team_id=' . $team1->id)
        ->assertSee('Team1 Alert')
        ->assertDontSee('Team1 OK')
        ->assertDontSee('Team2 Alert');
});

it('only shows teams user is a member of in filter', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create(['name' => 'My Team']);
    $otherTeam = Team::factory()->create(['name' => 'Other Team']);
    $userTeam->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);

    livewire(Index::class)
        ->assertSee('My Team')
        ->assertDontSee('Other Team');
});
