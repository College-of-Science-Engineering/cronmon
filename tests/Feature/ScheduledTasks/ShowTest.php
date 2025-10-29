<?php

use App\Livewire\ScheduledTasks\Show;
use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('can render the show page', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Backup Task',
        'description' => 'Daily database backup',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSeeLivewire(Show::class)
        ->assertSee('Backup Task')
        ->assertSee('Daily database backup');
});

it('displays task status with correct badge color', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'ok',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Ok');
});

it('displays team name', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Engineering Team']);
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Engineering Team');
});

it('displays never when task has not checked in', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'last_checked_in_at' => null,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Never');
});

it('displays last check-in time when available', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'last_checked_in_at' => now()->subHours(2),
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('2 hours ago');
});

it('displays simple schedule in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('1h');
});

it('displays cron schedule in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'cron',
        'schedule_value' => '0 3 * * *',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Cron: 0 3 * * *');
});

it('displays timezone in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'timezone' => 'America/New_York',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('America/New_York');
});

it('displays grace period in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'grace_period_minutes' => 15,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('15 minutes');
});

it('displays check-in token in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee($task->unique_check_in_token);
});

it('displays creator name in details tab', function () {
    // Arrange
    $creator = User::factory()->create([
        'forenames' => 'John',
        'surname' => 'Doe',
    ]);
    $team = Team::factory()->create();
    $team->users()->attach($creator);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'created_by' => $creator->id,
    ]);

    // Act & Assert
    $this->actingAs($creator)
        ->get("/tasks/{$task->id}")
        ->assertSee('John Doe');
});

it('displays empty state when no task runs in history tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('No check-ins yet');
});

it('displays task runs in history tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    TaskRun::factory()->create([
        'scheduled_task_id' => $task->id,
        'checked_in_at' => now(),
        'was_late' => false,
        'lateness_minutes' => null,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('On Time');
});

it('displays late task runs with lateness in history tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    TaskRun::factory()->create([
        'scheduled_task_id' => $task->id,
        'checked_in_at' => now(),
        'was_late' => true,
        'lateness_minutes' => 25,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Late')
        ->assertSee('25 min');
});

it('limits task runs to 20 most recent', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Create 25 task runs
    TaskRun::factory()->count(25)->create([
        'scheduled_task_id' => $task->id,
    ]);

    // Act
    $this->actingAs($user);
    $component = livewire(Show::class, ['task' => $task]);

    // Assert - only 20 should be loaded
    expect($component->task->taskRuns)->toHaveCount(20);
});

it('displays empty state when no alerts in alerts tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('No alerts')
        ->assertSee('No alerts have been triggered for this task.');
});

it('displays alerts in alerts tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'message' => 'Task missed its scheduled run',
        'triggered_at' => now(),
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Missed')
        ->assertSee('Task missed its scheduled run');
});

it('displays different alert types with correct badges', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'late',
        'message' => 'Task was late',
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'recovered',
        'message' => 'Task has recovered',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Late')
        ->assertSee('Recovered');
});

it('displays unacknowledged badge for alerts not acknowledged', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'acknowledged_at' => null,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Unacknowledged');
});

it('displays acknowledgment time for acknowledged alerts', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'acknowledged_at' => now()->subHours(1),
        'acknowledged_by' => $user->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('1 hour ago');
});

it('limits alerts to 20 most recent', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Create 25 alerts
    Alert::factory()->count(25)->create([
        'scheduled_task_id' => $task->id,
    ]);

    // Act
    $this->actingAs($user);
    $component = livewire(Show::class, ['task' => $task]);

    // Assert - only 20 should be loaded
    expect($component->task->alerts)->toHaveCount(20);
});

it('has edit button linking to edit page', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Edit')
        ->assertSee('open-task-form', false); // Check raw HTML
});

it('has back to list button', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}")
        ->assertSee('Back to List')
        ->assertSee(route('tasks.index', [], false));
});

it('displays quick start curl command in details tab', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    $this->actingAs($user)
        ->get(route('tasks.show', $task))
        ->assertSee('Quick Start')
        ->assertSee('curl')
        ->assertSee($task->getPingUrl());
});

it('has api tab with advanced examples', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    $this->actingAs($user)
        ->get(route('tasks.show', $task))
        ->assertSee('API')
        ->assertSee('Basic GET Request')
        ->assertSee('Track Execution Time (Start/Finish)')
        ->assertSee('POST with JSON Data')
        ->assertSee('Simple Cron Job Integration');
});

it('api tab displays actual task ping url in all examples', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    $pingUrl = $task->getPingUrl();

    // Act & Assert
    $response = $this->actingAs($user)
        ->get(route('tasks.show', $task));

    // Should appear in API tab (4 examples: basic GET, start, finish, simple cron) = 4 times
    // Note: Start/finish section has 2 curl calls
    expect(substr_count($response->getContent(), $pingUrl))->toBe(6);
});

it('can silence task alerts from the switch', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);

    livewire(Show::class, ['task' => $task])
        ->set('silenceEnabled', true);

    $silencedUntil = $task->fresh()->alerts_silenced_until;

    expect($silencedUntil)->not->toBeNull()
        ->and($silencedUntil->isFuture())->toBeTrue();
});

it('can clear task alert silence', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'alerts_silenced_until' => now()->addHours(3),
    ]);

    $this->actingAs($user);

    livewire(Show::class, ['task' => $task])
        ->set('silenceEnabled', false);

    expect($task->fresh()->alerts_silenced_until)->toBeNull();
});

it('can set a custom silence window for a task', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    $customUntilLocal = now()
        ->addHours(4)
        ->setTimezone(config('app.timezone'))
        ->format('Y-m-d\TH:i');

    $this->actingAs($user);

    livewire(Show::class, ['task' => $task])
        ->set('silenceEnabled', true)
        ->set('silenceSelection', 'custom')
        ->set('silenceCustomUntil', $customUntilLocal);

    $stored = $task->fresh()->alerts_silenced_until;

    expect($stored)->not->toBeNull()
        ->and($stored->format('Y-m-d\TH:i'))->toBe(
            Carbon::parse($customUntilLocal, config('app.timezone'))
                ->setTimezone('UTC')
                ->format('Y-m-d\TH:i')
        );
});
