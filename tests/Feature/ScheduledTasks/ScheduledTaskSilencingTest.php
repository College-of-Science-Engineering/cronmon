<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns false when task is not silenced', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    expect($task->isSilenced())->toBeFalse();
    expect($task->getSilencedCause())->toBeNull();
    expect($task->getSilencedUntil())->toBeNull();
});

it('returns true when task is silenced directly', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'alerts_silenced_until' => now()->addHours(2),
    ]);

    // Act & Assert
    expect($task->isSilenced())->toBeTrue();
    expect($task->getSilencedCause())->toBe('task');
    expect($task->getSilencedUntil())->not->toBeNull();
});

it('returns true when team is silenced', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'alerts_silenced_until' => now()->addHours(2),
    ]);
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    expect($task->isSilenced())->toBeTrue();
    expect($task->getSilencedCause())->toBe('team');
    expect($task->getSilencedUntil())->not->toBeNull();
});

it('returns false when silence period has expired for task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'alerts_silenced_until' => now()->subHour(),
    ]);

    // Act & Assert
    expect($task->isSilenced())->toBeFalse();
    expect($task->getSilencedCause())->toBeNull();
    expect($task->getSilencedUntil())->toBeNull();
});

it('returns false when silence period has expired for team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'alerts_silenced_until' => now()->subHour(),
    ]);
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    expect($task->isSilenced())->toBeFalse();
    expect($task->getSilencedCause())->toBeNull();
    expect($task->getSilencedUntil())->toBeNull();
});

it('prioritizes task silence over team silence in cause', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'alerts_silenced_until' => now()->addHours(3),
    ]);
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'alerts_silenced_until' => now()->addHours(1),
    ]);

    // Act & Assert
    expect($task->isSilenced())->toBeTrue();
    expect($task->getSilencedCause())->toBe('task'); // Task takes priority
});

it('returns correct silenced until time for task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);
    $silencedUntil = now()->addHours(2);
    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'alerts_silenced_until' => $silencedUntil,
    ]);

    // Act & Assert
    expect($task->getSilencedUntil()->toDateTimeString())->toBe($silencedUntil->toDateTimeString());
});

it('returns correct silenced until time for team', function () {
    // Arrange
    $user = User::factory()->create();
    $silencedUntil = now()->addHours(3);
    $team = Team::factory()->create([
        'alerts_silenced_until' => $silencedUntil,
    ]);
    $team->users()->attach($user);
    $task = ScheduledTask::factory()->create(['team_id' => $team->id]);

    // Act & Assert
    expect($task->getSilencedUntil()->toDateTimeString())->toBe($silencedUntil->toDateTimeString());
});

it('team isSilenced returns true when silenced', function () {
    // Arrange
    $team = Team::factory()->create([
        'alerts_silenced_until' => now()->addHours(2),
    ]);

    // Act & Assert
    expect($team->isSilenced())->toBeTrue();
});

it('team isSilenced returns false when not silenced', function () {
    // Arrange
    $team = Team::factory()->create();

    // Act & Assert
    expect($team->isSilenced())->toBeFalse();
});

it('team isSilenced returns false when silence period expired', function () {
    // Arrange
    $team = Team::factory()->create([
        'alerts_silenced_until' => now()->subHour(),
    ]);

    // Act & Assert
    expect($team->isSilenced())->toBeFalse();
});
