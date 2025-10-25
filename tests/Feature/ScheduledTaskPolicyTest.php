<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows any authenticated user to view any tasks', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    expect($user->can('viewAny', ScheduledTask::class))->toBeTrue();
});

it('allows any authenticated user to create tasks', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    expect($user->can('create', ScheduledTask::class))->toBeTrue();
});

it('allows user to view task from their team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($user->can('view', $task))->toBeTrue();
});

it('denies user from viewing task from another team', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($userWithoutAccess->can('view', $task))->toBeFalse();
});

it('allows user to update task from their team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($user->can('update', $task))->toBeTrue();
});

it('denies user from updating task from another team', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($userWithoutAccess->can('update', $task))->toBeFalse();
});

it('allows user to delete task from their team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($user->can('delete', $task))->toBeTrue();
});

it('denies user from deleting task from another team', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($userWithoutAccess->can('delete', $task))->toBeFalse();
});

it('allows user in multiple teams to access tasks from all their teams', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team1->users()->attach($user);
    $team2->users()->attach($user);

    $task1 = ScheduledTask::factory()->create(['team_id' => $team1->id]);
    $task2 = ScheduledTask::factory()->create(['team_id' => $team2->id]);

    // Act & Assert
    expect($user->can('view', $task1))->toBeTrue();
    expect($user->can('view', $task2))->toBeTrue();
    expect($user->can('update', $task1))->toBeTrue();
    expect($user->can('update', $task2))->toBeTrue();
    expect($user->can('delete', $task1))->toBeTrue();
    expect($user->can('delete', $task2))->toBeTrue();
});

it('allows user to restore task from their team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($user->can('restore', $task))->toBeTrue();
});

it('allows user to force delete task from their team', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    expect($user->can('forceDelete', $task))->toBeTrue();
});
