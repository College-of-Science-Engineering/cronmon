<?php

use App\Livewire\Dashboard;
use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can render the dashboard', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSee('Dashboard');
});

it('displays status counts correctly', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    ScheduledTask::factory()->create(['team_id' => $team->id, 'status' => 'ok']);
    ScheduledTask::factory()->create(['team_id' => $team->id, 'status' => 'ok']);
    ScheduledTask::factory()->create(['team_id' => $team->id, 'status' => 'alerting']);
    ScheduledTask::factory()->create(['team_id' => $team->id, 'status' => 'pending']);
    ScheduledTask::factory()->create(['team_id' => $team->id, 'status' => 'paused']);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('2') // OK count
        ->assertSee('1') // Alerting count
        ->assertSee('1') // Pending count
        ->assertSee('1'); // Paused count
});

it('only shows tasks from users teams', function () {
    // Arrange
    $userWithAccess = User::factory()->create();
    $teamWithAccess = Team::factory()->create();
    $teamWithAccess->users()->attach($userWithAccess);

    $userWithoutAccess = User::factory()->create();
    $teamWithoutAccess = Team::factory()->create();
    $teamWithoutAccess->users()->attach($userWithoutAccess);

    ScheduledTask::factory()->create(['team_id' => $teamWithAccess->id, 'status' => 'ok']);
    ScheduledTask::factory()->create(['team_id' => $teamWithAccess->id, 'status' => 'ok']);
    ScheduledTask::factory()->create(['team_id' => $teamWithoutAccess->id, 'status' => 'ok']);

    // Act
    $response = $this->actingAs($userWithAccess)->get('/');

    // Assert
    $response->assertSee('2'); // Should see 2 OK tasks from their team
});

it('displays empty state when no alerts exist', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('All clear!')
        ->assertSee('No recent alerts');
});

it('displays recent alerts', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Database Backup',
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'message' => 'Task has not checked in',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('Database Backup')
        ->assertSee('Missed')
        ->assertSee('Task has not checked in');
});

it('displays multiple recent alerts', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task1 = ScheduledTask::factory()->create(['team_id' => $team->id, 'name' => 'Task 1']);
    $task2 = ScheduledTask::factory()->create(['team_id' => $team->id, 'name' => 'Task 2']);

    Alert::factory()->create(['scheduled_task_id' => $task1->id, 'alert_type' => 'missed']);
    Alert::factory()->create(['scheduled_task_id' => $task2->id, 'alert_type' => 'late']);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('Task 1')
        ->assertSee('Task 2')
        ->assertSee('Missed')
        ->assertSee('Late');
});

it('limits alerts to 10 most recent', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Create 15 alerts with unique messages
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'oldest alert first', 'triggered_at' => now()->subMinutes(15)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'oldest alert second', 'triggered_at' => now()->subMinutes(14)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'oldest alert third', 'triggered_at' => now()->subMinutes(13)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'oldest alert fourth', 'triggered_at' => now()->subMinutes(12)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'oldest alert fifth', 'triggered_at' => now()->subMinutes(11)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert first', 'triggered_at' => now()->subMinutes(10)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert second', 'triggered_at' => now()->subMinutes(9)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert third', 'triggered_at' => now()->subMinutes(8)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert fourth', 'triggered_at' => now()->subMinutes(7)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert fifth', 'triggered_at' => now()->subMinutes(6)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert sixth', 'triggered_at' => now()->subMinutes(5)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert seventh', 'triggered_at' => now()->subMinutes(4)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert eighth', 'triggered_at' => now()->subMinutes(3)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'recent alert ninth', 'triggered_at' => now()->subMinutes(2)]);
    Alert::factory()->create(['scheduled_task_id' => $task->id, 'message' => 'newest alert', 'triggered_at' => now()->subMinutes(1)]);

    // Act
    $response = $this->actingAs($user)->get('/');

    // Assert - should see most recent 10, not oldest 5
    $response->assertSee('newest alert');
    $response->assertSee('recent alert first');
    $response->assertDontSee('oldest alert first');
    $response->assertDontSee('oldest alert fifth');
});

it('displays empty state when no check-ins exist', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('No check-ins yet')
        ->assertSee('Waiting for tasks to check in');
});

it('displays recent check-ins', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Database Backup',
    ]);

    TaskRun::factory()->create([
        'scheduled_task_id' => $task->id,
        'checked_in_at' => now(),
        'was_late' => false,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('Database Backup')
        ->assertSee('On time');
});

it('displays late badge for late check-ins', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create(['team_id' => $team->id, 'name' => 'Late Task']);

    TaskRun::factory()->create([
        'scheduled_task_id' => $task->id,
        'was_late' => true,
        'lateness_minutes' => 15,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('Late Task')
        ->assertSee('Late')
        ->assertSee('15 min late');
});

it('limits check-ins to 10 most recent', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create(['team_id' => $team->id, 'name' => 'Test Task']);

    // Create 15 check-ins
    for ($i = 1; $i <= 15; $i++) {
        TaskRun::factory()->create([
            'scheduled_task_id' => $task->id,
            'checked_in_at' => now()->subMinutes(15 - $i),
        ]);
    }

    // Act
    $response = $this->actingAs($user)->get('/');

    // Assert - should see task name (appears in recent 10 check-ins)
    $response->assertSee('Test Task');
});

it('does not display tasks from other teams', function () {
    // Arrange
    $userWithAccess = User::factory()->create();
    $teamWithAccess = Team::factory()->create();
    $teamWithAccess->users()->attach($userWithAccess);

    $userWithoutAccess = User::factory()->create();
    $teamWithoutAccess = Team::factory()->create();
    $teamWithoutAccess->users()->attach($userWithoutAccess);

    $taskWithAccess = ScheduledTask::factory()->create([
        'team_id' => $teamWithAccess->id,
        'name' => 'My Task',
    ]);

    $taskWithoutAccess = ScheduledTask::factory()->create([
        'team_id' => $teamWithoutAccess->id,
        'name' => 'Secret Task',
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $taskWithAccess->id,
        'message' => 'My Alert',
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $taskWithoutAccess->id,
        'message' => 'Secret Alert',
    ]);

    // Act
    $response = $this->actingAs($userWithAccess)->get('/');

    // Assert
    $response->assertSee('My Task');
    $response->assertSee('My Alert');
    $response->assertDontSee('Secret Task');
    $response->assertDontSee('Secret Alert');
});

it('has create new task button', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/')
        ->assertSee('New Task');
});
