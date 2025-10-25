<?php

use App\Mail\TaskMissedNotification;
use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('detects and alerts for task that has missed its schedule', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(20), // 15 minutes late (5+10+5)
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - task status updated
    $task->refresh();
    expect($task->status)->toBe('alerting');

    // Assert - alert created
    $alert = Alert::where('scheduled_task_id', $task->id)->first();
    expect($alert)->not->toBeNull();
    expect($alert->alert_type)->toBe('missed');
    expect($alert->acknowledged_at)->toBeNull();

    // Assert - email sent to team members
    Mail::assertSent(TaskMissedNotification::class, function ($mail) use ($user, $task) {
        return $mail->hasTo($user->email) && $mail->task->id === $task->id;
    });
});

it('does not alert for task that is on time', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(30), // On time
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - no changes
    $task->refresh();
    expect($task->status)->toBe('ok');
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBe(0);
    Mail::assertNothingSent();
});

it('does not alert for task that is late but within grace period', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(10), // 5 minutes late but within grace
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - no changes
    $task->refresh();
    expect($task->status)->toBe('ok');
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBe(0);
    Mail::assertNothingSent();
});

it('does not create duplicate alerts for already alerting task', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(30), // Very late
        'status' => 'alerting', // Already alerting
    ]);

    // Create existing alert
    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'triggered_at' => now()->subMinutes(10),
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - no new alert created
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBe(1);
    Mail::assertNothingSent();
});

it('creates recovery alert when task comes back online', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(3), // Recently checked in
        'status' => 'alerting', // Was alerting
    ]);

    // Create existing missed alert
    Alert::factory()->create([
        'scheduled_task_id' => $task->id,
        'alert_type' => 'missed',
        'triggered_at' => now()->subMinutes(30),
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - status changed to ok
    $task->refresh();
    expect($task->status)->toBe('ok');

    // Assert - recovery alert created
    $recoveryAlert = Alert::where('scheduled_task_id', $task->id)
        ->where('alert_type', 'recovered')
        ->first();
    expect($recoveryAlert)->not->toBeNull();

    // Assert - email sent about recovery
    Mail::assertSent(TaskMissedNotification::class);
});

it('sends email to all team members', function () {
    // Arrange
    Mail::fake();

    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);
    $user3 = User::factory()->create(['email' => 'user3@example.com']);

    $team = Team::factory()->create();
    $team->users()->attach([$user1->id, $user2->id, $user3->id]);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(20),
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - email sent to all team members
    Mail::assertSent(TaskMissedNotification::class, 3);
    Mail::assertSent(TaskMissedNotification::class, fn ($mail) => $mail->hasTo('user1@example.com'));
    Mail::assertSent(TaskMissedNotification::class, fn ($mail) => $mail->hasTo('user2@example.com'));
    Mail::assertSent(TaskMissedNotification::class, fn ($mail) => $mail->hasTo('user3@example.com'));
});

it('handles task with cron schedule', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'cron',
        'schedule_value' => '0 3 * * *', // Daily at 3am
        'grace_period_minutes' => 30,
        'last_checked_in_at' => now()->subHours(25)->setTime(3, 0), // Missed yesterday's run
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - task is now alerting
    $task->refresh();
    expect($task->status)->toBe('alerting');
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBeGreaterThan(0);
});

it('does not alert for paused tasks', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subMinutes(30), // Very late
        'status' => 'paused', // Paused
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - no alerts
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBe(0);
    Mail::assertNothingSent();
});

it('does not alert for pending tasks that have never checked in', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '5m',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => null, // Never checked in
        'status' => 'pending',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - no alerts
    expect(Alert::where('scheduled_task_id', $task->id)->count())->toBe(0);
    Mail::assertNothingSent();
});

it('includes helpful information in alert message', function () {
    // Arrange
    Mail::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Database Backup',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'grace_period_minutes' => 10,
        'last_checked_in_at' => now()->subHours(2),
        'status' => 'ok',
    ]);

    // Act
    $this->artisan('tasks:check-missed')->assertSuccessful();

    // Assert - alert message contains useful info
    $alert = Alert::where('scheduled_task_id', $task->id)->first();
    expect($alert->message)->toContain('Database Backup');
});
