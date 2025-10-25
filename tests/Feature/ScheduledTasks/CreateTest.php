<?php

use App\Livewire\ScheduledTasks\Create;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('can render the create form', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user)
        ->get('/tasks/create')
        ->assertSeeLivewire(Create::class)
        ->assertSee('Create Scheduled Task');
});

it('can create a scheduled task with simple schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('form.name', 'Daily Backup')
        ->set('form.description', 'Backup the database')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->set('form.timezone', 'UTC')
        ->set('form.grace_period_minutes', 15)
        ->call('save');

    // Assert
    $task = ScheduledTask::where('name', 'Daily Backup')->first();
    expect($task)->not->toBeNull();
    expect($task->description)->toBe('Backup the database');
    expect($task->schedule_type)->toBe('simple');
    expect($task->schedule_value)->toBe('1h');
    expect($task->timezone)->toBe('UTC');
    expect($task->grace_period_minutes)->toBe(15);
    expect($task->team_id)->toBe($team->id);
    expect($task->created_by)->toBe($user->id);
    expect($task->status)->toBe('pending');
    expect($task->unique_check_in_token)->not->toBeNull();
});

it('can create a scheduled task with cron schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('form.name', 'Nightly Job')
        ->set('form.schedule_type', 'cron')
        ->set('form.schedule_value', '0 3 * * *')
        ->set('form.timezone', 'America/New_York')
        ->set('form.grace_period_minutes', 30)
        ->call('save');

    // Assert
    $task = ScheduledTask::where('name', 'Nightly Job')->first();
    expect($task)->not->toBeNull();
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 3 * * *');
    expect($task->timezone)->toBe('America/New_York');
});

it('validates required fields', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert
    $this->actingAs($user);
    livewire(Create::class)
        ->set('form.name', '')
        ->set('form.schedule_value', '')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.schedule_value']);

    // Ensure no task was created
    expect(ScheduledTask::count())->toBe(0);
});

it('validates grace period is within range', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act & Assert - too low
    $this->actingAs($user);
    livewire(Create::class)
        ->set('form.name', 'Test Task')
        ->set('form.schedule_value', '1h')
        ->set('form.grace_period_minutes', 0)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);

    // Act & Assert - too high
    livewire(Create::class)
        ->set('form.name', 'Test Task')
        ->set('form.schedule_value', '1h')
        ->set('form.grace_period_minutes', 2000)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);
});

it('redirects to show page after creating task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    // Act
    $this->actingAs($user);
    livewire(Create::class)
        ->set('form.name', 'New Task')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '5m')
        ->call('save')
        ->assertRedirect();

    // Assert redirect went to correct route
    $task = ScheduledTask::where('name', 'New Task')->first();
    expect($task)->not->toBeNull();
});
