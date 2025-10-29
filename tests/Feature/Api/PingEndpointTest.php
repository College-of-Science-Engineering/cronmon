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
    $this->travel(1)->seconds(); // Ensure different timestamps without waiting
    $this->get("/ping/{$task->unique_check_in_token}");
    $this->travel(1)->seconds();
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

// Start/Finish Tracking Tests

it('can ping with start parameter', function () {
    // Arrange
    $task = ScheduledTask::factory()->create(['status' => 'pending']);

    // Act
    $response = $this->get("/ping/{$task->unique_check_in_token}?start");

    // Assert
    $response->assertSuccessful();
    $response->assertJson(['message' => 'Check-in recorded']);

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->started_at)->not->toBeNull();
    expect($taskRun->finished_at)->toBeNull();
    expect($taskRun->execution_time_seconds)->toBeNull();
});

it('does not update task status on start ping', function () {
    // Arrange
    $task = ScheduledTask::factory()->create([
        'status' => 'pending',
        'last_checked_in_at' => null,
    ]);

    // Act
    $this->get("/ping/{$task->unique_check_in_token}?start");

    // Assert
    $task->refresh();
    expect($task->status)->toBe('pending');
    expect($task->last_checked_in_at)->toBeNull();
});

it('can ping with finish parameter after start', function () {
    // Arrange
    $task = ScheduledTask::factory()->create(['status' => 'pending']);

    // Act - start then finish
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(2)->seconds(); // Ensure execution time is measurable
    $this->get("/ping/{$task->unique_check_in_token}?finish");

    // Assert
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->started_at)->not->toBeNull();
    expect($taskRun->finished_at)->not->toBeNull();
    expect($taskRun->execution_time_seconds)->toBeGreaterThanOrEqual(2);
});

it('updates task status to ok on finish ping', function () {
    // Arrange
    $task = ScheduledTask::factory()->create(['status' => 'pending']);

    // Act
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->get("/ping/{$task->unique_check_in_token}?finish");

    // Assert
    $task->refresh();
    expect($task->status)->toBe('ok');
    expect($task->last_checked_in_at)->not->toBeNull();
});

it('can ping with finish parameter without prior start', function () {
    // Arrange
    $task = ScheduledTask::factory()->create(['status' => 'pending']);

    // Act
    $response = $this->get("/ping/{$task->unique_check_in_token}?finish");

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun)->not->toBeNull();
    expect($taskRun->started_at)->toBeNull();
    expect($taskRun->finished_at)->not->toBeNull();
    expect($taskRun->execution_time_seconds)->toBeNull();

    $task->refresh();
    expect($task->status)->toBe('ok');
});

it('handles multiple starts without finish', function () {
    // Arrange
    $task = ScheduledTask::factory()->create();

    // Act - send multiple start pings
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(1)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(1)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?start");

    // Assert - should have 3 incomplete TaskRuns
    $incompleteRuns = TaskRun::where('scheduled_task_id', $task->id)
        ->whereNotNull('started_at')
        ->whereNull('finished_at')
        ->count();
    expect($incompleteRuns)->toBe(3);
});

it('completes most recent start when finish arrives', function () {
    // Arrange
    $task = ScheduledTask::factory()->create();

    // Act - multiple starts, then one finish
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(1)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(1)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(1)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?finish");

    // Assert - only the most recent start should be completed
    $completedRuns = TaskRun::where('scheduled_task_id', $task->id)
        ->whereNotNull('started_at')
        ->whereNotNull('finished_at')
        ->count();
    expect($completedRuns)->toBe(1);

    $incompleteRuns = TaskRun::where('scheduled_task_id', $task->id)
        ->whereNotNull('started_at')
        ->whereNull('finished_at')
        ->count();
    expect($incompleteRuns)->toBe(2);
});

it('calculates execution time correctly', function () {
    // Arrange
    $task = ScheduledTask::factory()->create();

    // Act
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $this->travel(3)->seconds();
    $this->get("/ping/{$task->unique_check_in_token}?finish");

    // Assert
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)
        ->whereNotNull('finished_at')
        ->first();

    expect($taskRun->execution_time_seconds)->toBeGreaterThanOrEqual(3);
    expect($taskRun->execution_time_seconds)->toBeLessThan(5); // Should be close to 3
});

it('can include data with start ping', function () {
    // Arrange
    $task = ScheduledTask::factory()->create();
    $data = ['started_by' => 'cron', 'batch_id' => 123];

    // Act
    $response = $this->postJson("/ping/{$task->unique_check_in_token}?start", [
        'data' => $data,
    ]);

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun->data)->toBe($data);
    expect($taskRun->started_at)->not->toBeNull();
    expect($taskRun->finished_at)->toBeNull();
});

it('can include data with finish ping', function () {
    // Arrange
    $task = ScheduledTask::factory()->create();
    $data = ['records_processed' => 450, 'status' => 'success'];

    // Act
    $this->get("/ping/{$task->unique_check_in_token}?start");
    $response = $this->postJson("/ping/{$task->unique_check_in_token}?finish", [
        'data' => $data,
    ]);

    // Assert
    $response->assertSuccessful();

    // Note: Finish ping doesn't update data on existing incomplete run
    // It would only set data if creating a new TaskRun (finish without start)
    $taskRun = TaskRun::where('scheduled_task_id', $task->id)
        ->whereNotNull('finished_at')
        ->first();
    expect($taskRun)->not->toBeNull();
});

it('plain ping still works as before', function () {
    // Arrange
    $task = ScheduledTask::factory()->create(['status' => 'pending']);

    // Act
    $response = $this->get("/ping/{$task->unique_check_in_token}");

    // Assert
    $response->assertSuccessful();

    $taskRun = TaskRun::where('scheduled_task_id', $task->id)->first();
    expect($taskRun->started_at)->toBeNull();
    expect($taskRun->finished_at)->toBeNull();
    expect($taskRun->execution_time_seconds)->toBeNull();
    expect($taskRun->checked_in_at)->not->toBeNull();

    $task->refresh();
    expect($task->status)->toBe('ok');
    expect($task->last_checked_in_at)->not->toBeNull();
});
