<?php

use App\Livewire\Teams\Create;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('displays the create team form', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    $this->actingAs($user)
        ->get('/teams/create')
        ->assertSeeLivewire(Create::class)
        ->assertSee('Create Team');
});

it('can create a new team', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'Engineering Team')
        ->call('save');

    // Assert
    $team = Team::where('name', 'Engineering Team')->first();
    expect($team)->not->toBeNull();
    expect($team->slug)->toBe('engineering-team');
});

it('automatically adds creator as team member', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'Engineering Team')
        ->call('save');

    // Assert
    $team = Team::where('name', 'Engineering Team')->first();
    expect($team->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

it('auto-generates slug from team name', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'My Cool Team!')
        ->call('save');

    // Assert
    $team = Team::where('name', 'My Cool Team!')->first();
    expect($team->slug)->toBe('my-cool-team');
});

it('ensures slug is unique', function () {
    // Arrange
    $user = User::factory()->create();
    Team::factory()->create(['name' => 'Engineering', 'slug' => 'engineering']);

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'Engineering')
        ->call('save');

    // Assert
    $teams = Team::where('name', 'Engineering')->get();
    expect($teams)->toHaveCount(2);
    expect($teams->pluck('slug')->unique())->toHaveCount(2);
});

it('requires team name', function () {
    // Arrange
    $user = User::factory()->create();
    $initialCount = Team::count(); // Account for auto-created personal team

    // Act & Assert
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    expect(Team::count())->toBe($initialCount);
});

it('requires team name to be at least 2 characters', function () {
    // Arrange
    $user = User::factory()->create();
    $initialCount = Team::count(); // Account for auto-created personal team

    // Act & Assert
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'A')
        ->call('save')
        ->assertHasErrors(['name' => 'min']);

    expect(Team::count())->toBe($initialCount);
});

it('requires team name to be no more than 255 characters', function () {
    // Arrange
    $user = User::factory()->create();
    $initialCount = Team::count(); // Account for auto-created personal team

    // Act & Assert
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', Str::repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['name' => 'max']);

    expect(Team::count())->toBe($initialCount);
});

it('redirects to team show page after creation', function () {
    // Arrange
    $user = User::factory()->create();

    // Act & Assert
    $this->actingAs($user);
    livewire(Create::class)
        ->set('name', 'Engineering Team')
        ->call('save')
        ->assertRedirect();

    $team = Team::where('name', 'Engineering Team')->first();
    expect($team)->not->toBeNull();
});

// Note: Authentication tests skipped - SSO not yet implemented
