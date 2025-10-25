<?php

use App\Livewire\ScheduledTasks\Edit;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('can render the edit form', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Existing Task',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->get("/tasks/{$task->id}/edit")
        ->assertSeeLivewire(Edit::class)
        ->assertSee('Edit Scheduled Task')
        ->assertSee('Existing Task');
});

it('can update a scheduled task with simple schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Name',
        'description' => 'Old description',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'timezone' => 'UTC',
        'grace_period_minutes' => 10,
    ]);

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Updated Task')
        ->set('form.description', 'Updated description')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '30m')
        ->set('form.timezone', 'America/New_York')
        ->set('form.grace_period_minutes', 20)
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->name)->toBe('Updated Task');
    expect($task->description)->toBe('Updated description');
    expect($task->schedule_type)->toBe('simple');
    expect($task->schedule_value)->toBe('30m');
    expect($task->timezone)->toBe('America/New_York');
    expect($task->grace_period_minutes)->toBe(20);
});

it('can update a scheduled task with cron schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Cron Task',
        'schedule_type' => 'cron',
        'schedule_value' => '0 1 * * *',
    ]);

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Updated Cron Task')
        ->set('form.schedule_type', 'cron')
        ->set('form.schedule_value', '0 3 * * *')
        ->set('form.timezone', 'Europe/London')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->name)->toBe('Updated Cron Task');
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 3 * * *');
    expect($task->timezone)->toBe('Europe/London');
});

it('can change schedule type from simple to cron', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
    ]);

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.schedule_type', 'cron')
        ->set('form.schedule_value', '0 * * * *')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 * * * *');
});

it('can change schedule type from cron to simple', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'schedule_type' => 'cron',
        'schedule_value' => '0 3 * * *',
    ]);

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '2h')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->schedule_type)->toBe('simple');
    expect($task->schedule_value)->toBe('2h');
});

it('validates required fields', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Original Name',
        'schedule_value' => '1h',
    ]);

    // Act & Assert
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', '')
        ->set('form.schedule_value', '')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.schedule_value']);

    // Ensure task was not updated
    $task->refresh();
    expect($task->name)->toBe('Original Name');
    expect($task->schedule_value)->toBe('1h');
});

it('validates grace period is within range', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'grace_period_minutes' => 10,
    ]);

    // Act & Assert - too low
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.grace_period_minutes', 0)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);

    // Act & Assert - too high
    livewire(Edit::class, ['task' => $task])
        ->set('form.grace_period_minutes', 2000)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);

    // Ensure grace period was not updated
    $task->refresh();
    expect($task->grace_period_minutes)->toBe(10);
});

it('does not change unique_check_in_token when updating', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Task with Token',
    ]);

    $originalToken = $task->unique_check_in_token;

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Updated Name')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->unique_check_in_token)->toBe($originalToken);
});

it('does not change created_by when updating', function () {
    // Arrange
    $originalCreator = User::factory()->create();
    $editor = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($originalCreator);
    $team->users()->attach($editor);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'created_by' => $originalCreator->id,
        'name' => 'Original Task',
    ]);

    // Act
    $this->actingAs($editor);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Edited Task')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->created_by)->toBe($originalCreator->id);
    expect($task->name)->toBe('Edited Task');
});

it('does not change team_id when updating', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Team Task',
    ]);

    $originalTeamId = $task->team_id;

    // Act
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Updated Team Task')
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->team_id)->toBe($originalTeamId);
});

it('redirects to show page after updating task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act & Assert
    $this->actingAs($user);
    livewire(Edit::class, ['task' => $task])
        ->set('form.name', 'Updated Task')
        ->call('save')
        ->assertRedirect(route('tasks.show', $task));
});
