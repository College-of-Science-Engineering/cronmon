<?php

use App\Livewire\ScheduledTasks\TaskFormModal;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);
uses()->group('scheduled-tasks');

it('can open create modal', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->assertSet('showModal', false)
        ->dispatch('open-task-form')
        ->assertSet('showModal', true)
        ->assertSet('taskId', null);
});

it('defaults to personal team on create', function () {
    // Arrange
    $user = User::factory()->create();
    $personalTeam = Team::factory()->create(['name' => $user->username]);
    $otherTeam = Team::factory()->create(['name' => 'Other Team']);
    $user->teams()->attach([$personalTeam->id, $otherTeam->id]);

    // Act & Assert
    actingAs($user);

    $component = Livewire::test(TaskFormModal::class);

    // Component should default to the team whose name matches the username
    $selectedTeam = Team::find($component->get('team_id'));
    expect($selectedTeam->name)->toBe($user->username);
});

it('can create a task with simple schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('team_id', $team->id)
        ->set('form.name', 'Test Task')
        ->set('form.description', 'Test description')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->set('form.timezone', 'UTC')
        ->set('form.grace_period_minutes', 10)
        ->call('save')
        ->assertDispatched('task-saved')
        ->assertSet('showModal', false);

    // Assert
    $task = ScheduledTask::where('name', 'Test Task')->first();
    expect($task)->not->toBeNull();
    expect($task->team_id)->toBe($team->id);
    expect($task->created_by)->toBe($user->id);
    expect($task->schedule_type)->toBe('simple');
    expect($task->schedule_value)->toBe('1h');
    expect($task->status)->toBe('pending');
});

it('can create a task with cron schedule', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('team_id', $team->id)
        ->set('form.name', 'Cron Task')
        ->set('form.schedule_type', 'cron')
        ->set('form.schedule_value', '0 3 * * *')
        ->call('save');

    // Assert
    $task = ScheduledTask::where('name', 'Cron Task')->first();
    expect($task)->not->toBeNull();
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 3 * * *');
});

it('validates required fields on create', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);

    // Assert no task was created
    expect(ScheduledTask::count())->toBe(0);
});

it('can open edit modal with existing task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Existing Task',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
    ]);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->assertSet('showModal', true)
        ->assertSet('taskId', $task->id)
        ->assertSet('form.name', 'Existing Task')
        ->assertSet('form.schedule_type', 'simple')
        ->assertSet('form.schedule_value', '1h')
        ->assertSet('team_id', $team->id);
});

it('can update an existing task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Original Name',
        'description' => 'Original description',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
    ]);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->set('form.name', 'Updated Name')
        ->set('form.description', 'Updated description')
        ->set('form.schedule_value', '6h')
        ->call('save')
        ->assertDispatched('task-saved')
        ->assertSet('showModal', false);

    // Assert
    $task->refresh();
    expect($task->name)->toBe('Updated Name');
    expect($task->description)->toBe('Updated description');
    expect($task->schedule_value)->toBe('6h');
    expect($task->team_id)->toBe($team->id); // Team unchanged
});

it('validates required fields on update', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Original Name',
    ]);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);

    // Assert - task name unchanged
    $task->refresh();
    expect($task->name)->toBe('Original Name');
});

it('prevents editing tasks from teams user does not belong to', function () {
    // Arrange
    $user = User::factory()->create();
    $userTeam = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user->teams()->attach($userTeam);
    $task = ScheduledTask::factory()->for($otherTeam)->create();

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->assertForbidden();
});

it('shows all teams in selection dropdown on create', function () {
    // Arrange
    $user = User::factory()->create();
    Team::factory()->create(['name' => 'Team Alpha']);
    Team::factory()->create(['name' => 'Team Beta']);
    Team::factory()->create(['name' => 'Team Gamma']);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->assertSee('Team Alpha')
        ->assertSee('Team Beta')
        ->assertSee('Team Gamma');
});

it('can change team when editing', function () {
    // Arrange
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['name' => 'Original Team']);
    $team2 = Team::factory()->create(['name' => 'New Team']);
    $user->teams()->attach([$team1->id, $team2->id]);
    $task = ScheduledTask::factory()->for($team1)->create(['created_by' => $user->id]);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->set('team_id', $team2->id)
        ->call('save');

    // Assert
    $task->refresh();
    expect($task->team_id)->toBe($team2->id);
});

it('can create task for specific team', function () {
    // Arrange
    $user = User::factory()->create();
    $personalTeam = Team::factory()->create(['name' => $user->username]);
    $workTeam = Team::factory()->create(['name' => 'Work Team']);
    $user->teams()->attach([$personalTeam->id, $workTeam->id]);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('team_id', $workTeam->id)
        ->set('form.name', 'Work Task')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->call('save');

    // Assert
    $task = ScheduledTask::where('name', 'Work Task')->first();
    expect($task)->not->toBeNull();
    expect($task->team_id)->toBe($workTeam->id);
});

it('resets form when opening create modal after edit', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Existing Task',
    ]);

    // Act & Assert
    actingAs($user);
    $component = Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->assertSet('form.name', 'Existing Task')
        ->dispatch('open-task-form') // Open create modal
        ->assertSet('taskId', null)
        ->assertSet('form.name', ''); // Form should be reset
});

it('redirects to task show page after create', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.name', 'New Task')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->call('save')
        ->assertRedirect();
});

it('redirects to task show page after update', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Original Task',
    ]);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->set('form.name', 'Updated Task')
        ->call('save')
        ->assertRedirect(route('tasks.show', $task));
});

it('validates timezone is valid', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act & Assert
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.name', 'Test Task')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->set('form.timezone', 'Invalid/Timezone')
        ->call('save')
        ->assertHasErrors(['form.timezone']);
});

it('validates grace period is between 1 and 1440 minutes', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act & Assert - too low
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.grace_period_minutes', 0)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);

    // Act & Assert - too high
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.grace_period_minutes', 1441)
        ->call('save')
        ->assertHasErrors(['form.grace_period_minutes']);
});

it('generates unique check-in token on create', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    // Act
    actingAs($user);
    Livewire::test(TaskFormModal::class)
        ->dispatch('open-task-form')
        ->set('form.name', 'Test Task')
        ->set('form.schedule_type', 'simple')
        ->set('form.schedule_value', '1h')
        ->call('save');

    // Assert
    $task = ScheduledTask::where('name', 'Test Task')->first();
    expect($task->unique_check_in_token)->not->toBeNull();
    expect(strlen($task->unique_check_in_token))->toBe(36); // UUID length
});
