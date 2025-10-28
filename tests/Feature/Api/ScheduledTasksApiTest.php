<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

it('returns scheduled tasks that belong to the authenticated users teams', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $otherTeam = Team::factory()->create();

    $matchingTask = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'name' => 'Database Backup',
    ]);

    ScheduledTask::factory()->for($otherTeam)->create();

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/tasks');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingTask->id)
        ->assertJsonPath('data.0.team_id', $team->id);
});

it('allows creating a scheduled task', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    Sanctum::actingAs($user);

    $payload = [
        'team_id' => $team->id,
        'name' => 'Nightly Backup',
        'description' => 'Runs every night to back up the database.',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'timezone' => 'UTC',
        'grace_period_minutes' => 15,
    ];

    $response = postJson('/api/v1/tasks', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Nightly Backup')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.team_id', $team->id);

    expect(ScheduledTask::where('name', 'Nightly Backup')->exists())->toBeTrue();
});

it('can silence a scheduled task', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
        'alerts_silenced_until' => null,
    ]);

    Sanctum::actingAs($user);

    $silenceUntil = now()->addHour();

    $response = postJson("/api/v1/tasks/{$task->id}/silence", [
        'silenced_until' => $silenceUntil->toISOString(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.silence.active', true)
        ->assertJsonPath('data.silence.until', $silenceUntil->copy()->setTimezone('UTC')->toIso8601String());
});

it('updates a scheduled task', function () {
    $user = User::factory()->create();
    $primaryTeam = Team::factory()->create();
    $secondaryTeam = Team::factory()->create();

    $primaryTeam->users()->attach($user);
    $secondaryTeam->users()->attach($user);

    $task = ScheduledTask::factory()->for($primaryTeam)->create([
        'created_by' => $user->id,
        'name' => 'Original Name',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'timezone' => 'UTC',
        'grace_period_minutes' => 10,
    ]);

    Sanctum::actingAs($user);

    $payload = [
        'team_id' => $secondaryTeam->id,
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'schedule_type' => 'cron',
        'schedule_value' => '0 12 * * *',
        'timezone' => 'Europe/London',
        'grace_period_minutes' => 30,
    ];

    $response = putJson("/api/v1/tasks/{$task->id}", $payload);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.team_id', $secondaryTeam->id)
        ->assertJsonPath('data.schedule_type', 'cron');

    $task->refresh();

    expect($task->team_id)->toBe($secondaryTeam->id);
    expect($task->schedule_type)->toBe('cron');
    expect($task->schedule_value)->toBe('0 12 * * *');
    expect($task->timezone)->toBe('Europe/London');
});

it('rejects updates to teams the user does not belong to', function () {
    $user = User::factory()->create();
    $ownedTeam = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    $ownedTeam->users()->attach($user);

    $task = ScheduledTask::factory()->for($ownedTeam)->create([
        'created_by' => $user->id,
        'name' => 'Original Name',
        'schedule_type' => 'simple',
        'schedule_value' => '1h',
        'timezone' => 'UTC',
        'grace_period_minutes' => 10,
    ]);

    Sanctum::actingAs($user);

    $response = putJson("/api/v1/tasks/{$task->id}", [
        'team_id' => $otherTeam->id,
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'schedule_type' => 'cron',
        'schedule_value' => '0 1 * * *',
        'timezone' => 'UTC',
        'grace_period_minutes' => 20,
    ]);

    $response->assertNotFound();
});

it('deletes a scheduled task', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    $task = ScheduledTask::factory()->for($team)->create([
        'created_by' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $response = deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertNoContent();
    expect(ScheduledTask::whereKey($task->id)->exists())->toBeFalse();
});

it('filters tasks by silenced status', function () {
    $user = User::factory()->create();

    $activeTeam = Team::factory()->create();
    $silencedTeam = Team::factory()->create();

    $activeTeam->users()->attach($user);
    $silencedTeam->users()->attach($user);

    $silencedTask = ScheduledTask::factory()->for($activeTeam)->create([
        'created_by' => $user->id,
    ]);
    $silencedTask->alerts_silenced_until = now()->addHour();
    $silencedTask->save();

    $teamSilencedTask = ScheduledTask::factory()->for($silencedTeam)->create([
        'created_by' => $user->id,
    ]);
    $silencedTeam->alerts_silenced_until = now()->addHours(2);
    $silencedTeam->save();

    $unsilencedTask = ScheduledTask::factory()->for($activeTeam)->create([
        'created_by' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $silencedResponse = getJson('/api/v1/tasks?filter[silenced]=true');
    $unsilencedResponse = getJson('/api/v1/tasks?filter[silenced]=false');

    $silencedIds = collect($silencedResponse->json('data'))->pluck('id');
    $unsilencedIds = collect($unsilencedResponse->json('data'))->pluck('id');

    expect($silencedIds)->toContain($silencedTask->id);
    expect($silencedIds)->toContain($teamSilencedTask->id);
    expect($silencedIds)->not()->toContain($unsilencedTask->id);

    expect($unsilencedIds)->toContain($unsilencedTask->id);
    expect($unsilencedIds)->not()->toContain($silencedTask->id);
    expect($unsilencedIds)->not()->toContain($teamSilencedTask->id);
});
