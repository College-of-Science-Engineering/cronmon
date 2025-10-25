<?php

use App\Models\ScheduledTask;
use App\Models\TaskRun;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can ping with GET request', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'pending',
    ]);

    // Act
    $response = $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    $response->assertSuccessful();
    $response->assertJson(['message' => 'Check-in recorded']);

    // Verify TaskRun was created
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->checked_in_at)->not->toBeNull();
    expect($taskRun->data)->toBeNull();
});

it('can ping with POST request', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act
    $response = $this->post("/ping/{$task->unique_check_in_token}");

    // Assert
    $response->assertSuccessful();
    $response->assertJson(['message' => 'Check-in recorded']);

    // Verify TaskRun was created
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->checked_in_at)->not->toBeNull();
});

it('can ping with POST request including data', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    $customData = [
        'execution_time' => 45.2,
        'records_processed' => 1250,
        'status_message' => 'Backup completed successfully',
    ];

    // Act
    $response = $this->postJson("/ping/{$task->unique_check_in_token}", [
        'data' => $customData,
    ]);

    // Assert
    $response->assertSuccessful();
    $response->assertJson(['message' => 'Check-in recorded']);

    // Verify TaskRun was created with data
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->data)->toBe($customData);
});

it('updates last_checked_in_at on task', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'last_checked_in_at' => null,
    ]);

    // Act
    $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    $task->refresh();
    expect($task->last_checked_in_at)->not->toBeNull();
});

it('creates multiple task runs for multiple pings', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act - ping 3 times
    $this->get("/ping/{$task->unique_check_in_token}");
    sleep(1); // Ensure different timestamps
    $this->get("/ping/{$task->unique_check_in_token}");
    sleep(1);
    $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    expect(TaskRun::where('scheduled_task_id', $task->id)->count())->toBe(3);
});

it('returns 404 for invalid token', function () {
    // Act
    $response = $this->get('/ping/invalid-token-12345');

    // Assert
    $response->assertNotFound();
});

it('returns 404 for non-existent token', function () {
    // Act
    $response = $this->get('/ping/00000000-0000-0000-0000-000000000000');

    // Assert
    $response->assertNotFound();
});

it('does not require authentication', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act - without being authenticated
    $response = $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    $response->assertSuccessful();
});

it('handles different data types in POST data field', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    $complexData = [
        'string' => 'test value',
        'integer' => 123,
        'float' => 45.67,
        'boolean' => true,
        'null' => null,
        'array' => [1, 2, 3],
        'nested' => [
            'key1' => 'value1',
            'key2' => 'value2',
        ],
    ];

    // Act
    $response = $this->postJson("/ping/{$task->unique_check_in_token}", [
        'data' => $complexData,
    ]);

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun->data)->toBe($complexData);
});

it('ignores extra fields in POST request', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act
    $response = $this->postJson("/ping/{$task->unique_check_in_token}", [
        'data' => ['message' => 'valid'],
        'malicious_field' => 'should be ignored',
        'another_field' => 'also ignored',
    ]);

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun->data)->toBe(['message' => 'valid']);
});

it('records check-in even if data field is missing in POST', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
    ]);

    // Act
    $response = $this->postJson("/ping/{$task->unique_check_in_token}", []);

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->data)->toBeNull();
});

it('sets task status to ok on first check-in', function () {
    // Arrange
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'status' => 'pending',
    ]);

    // Act
    $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    $task->refresh();
    expect($task->status)->toBe('ok');
});

it('does not create TaskRun for invalid token', function () {
    // Arrange
    $initialCount = TaskRun::count();

    // Act
    $this->get('/ping/invalid-token');

    // Assert
    expect(TaskRun::count())->toBe($initialCount);
});
