<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

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
