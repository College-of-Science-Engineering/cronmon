<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows any authenticated user to view any teams', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    expect($user->can('viewAny', Team::class))->toBeTrue();
});

it('allows any authenticated user to create teams', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    expect($user->can('create', Team::class))->toBeTrue();
});

it('allows user to view team they belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    expect($user->can('view', $team))->toBeTrue();
});

it('allows any user to view any team', function () {
    // Arrange
    $userWithoutMembership = User::factory()->create();
    $userWithMembership = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithMembership);

    // Act & Assert - all users can view all teams
    expect($userWithoutMembership->can('view', $team))->toBeTrue();
    expect($userWithMembership->can('view', $team))->toBeTrue();
});

it('allows user to update team they belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    expect($user->can('update', $team))->toBeTrue();
});

it('denies user from updating team they do not belong to', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    // Act & Assert
    expect($userWithoutAccess->can('update', $team))->toBeFalse();
});

it('allows user to delete team they belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    expect($user->can('delete', $team))->toBeTrue();
});

it('denies user from deleting team they do not belong to', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    // Act & Assert
    expect($userWithoutAccess->can('delete', $team))->toBeFalse();
});

it('allows user in multiple teams to access all their teams', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $team1->users()->attach($user);
    $team2->users()->attach($user);

    // Act & Assert
    expect($user->can('view', $team1))->toBeTrue();
    expect($user->can('view', $team2))->toBeTrue();
    expect($user->can('update', $team1))->toBeTrue();
    expect($user->can('update', $team2))->toBeTrue();
    expect($user->can('delete', $team1))->toBeTrue();
    expect($user->can('delete', $team2))->toBeTrue();
});

it('allows user to restore team they belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    expect($user->can('restore', $team))->toBeTrue();
});

it('allows user to force delete team they belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    expect($user->can('forceDelete', $team))->toBeTrue();
});

it('denies user from restoring team they do not belong to', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    // Act & Assert
    expect($userWithoutAccess->can('restore', $team))->toBeFalse();
});

it('denies user from force deleting team they do not belong to', function () {
    // Arrange
    $userWithoutAccess = User::factory()->create();
    $userWithAccess = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($userWithAccess);

    // Act & Assert
    expect($userWithoutAccess->can('forceDelete', $team))->toBeFalse();
});
