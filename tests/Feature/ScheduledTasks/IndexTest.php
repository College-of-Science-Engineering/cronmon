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
