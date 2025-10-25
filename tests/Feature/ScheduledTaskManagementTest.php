<?php

use App\Livewire\ScheduledTasks\Create as CreateTask;
use App\Livewire\ScheduledTasks\Edit as EditTask;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a scheduled task with valid data', function () {
    // Arrange
    $user = User::factory()->create();

    $this->actingAs($user);

    // Act
    $component = Livewire::test(CreateTask::class)
        ->set('form.name', 'Nightly Backup')
        ->set('form.description', 'Runs every night at midnight')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', 'daily')
        ->set('form.timezone', 'UTC')
        ->set('form.grace_period_minutes', 15)
        ->call('save');

    $task = ScheduledTask::firstWhere('name', 'Nightly Backup');

    // Assert
    expect($task)->not->toBeNull();
    expect($task->team_id)->toBe($user->personalTeam()->id);
    expect($task->status)->toBe('pending');
    expect($task->unique_check_in_token)->not->toBeNull();

    $component->assertRedirect(route('tasks.show', $task));
});

it('validates required fields when creating a task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $this->actingAs($user);

    // Act & Assert
    Livewire::test(CreateTask::class)
        ->set('form.name', '')
        ->set('form.schedule_value', '')
        ->set('form.timezone', 'Invalid/Zone')
        ->set('form.grace_period_minutes', 0)
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
            'form.schedule_value' => 'required',
            'form.timezone' => 'timezone',
            'form.grace_period_minutes' => 'min',
        ]);

    expect(ScheduledTask::count())->toBe(0);
});

it('updates a scheduled task with valid data', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'created_by' => $user->id,
        'name' => 'Legacy Job',
        'schedule_type' => 'simple',
        'schedule_value' => 'daily',
        'grace_period_minutes' => 10,
    ]);

    $this->actingAs($user);

    // Act
    $component = Livewire::test(EditTask::class, ['task' => $task])
        ->set('form.name', 'Updated Job')
        ->set('form.description', 'Now runs every hour')
        ->set('form.schedule_type', 'cron')
        ->set('form.schedule_value', '0 * * * *')
        ->set('form.timezone', 'Europe/London')
        ->set('form.grace_period_minutes', 30)
        ->call('save');

    // Assert
    $task->refresh();

    expect($task->name)->toBe('Updated Job');
    expect($task->description)->toBe('Now runs every hour');
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 * * * *');
    expect($task->timezone)->toBe('Europe/London');
    expect($task->grace_period_minutes)->toBe(30);

    $component->assertRedirect(route('tasks.show', $task));
});

it('validates fields when updating a task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'created_by' => $user->id,
        'schedule_type' => 'simple',
        'grace_period_minutes' => 15,
    ]);

    $this->actingAs($user);

    // Act & Assert
    Livewire::test(EditTask::class, ['task' => $task])
        ->set('form.schedule_type', 'invalid-type')
        ->set('form.grace_period_minutes', 2000)
        ->call('save')
        ->assertHasErrors([
            'form.schedule_type' => 'in',
            'form.grace_period_minutes' => 'max',
        ]);

    $task->refresh();

    expect($task->schedule_type)->toBe('simple');
    expect($task->grace_period_minutes)->toBe(15);
});
