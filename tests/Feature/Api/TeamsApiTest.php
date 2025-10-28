<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('lists only the teams the user belongs to', function () {
    $user = User::factory()->create();

    $teamA = Team::factory()->create(['name' => 'Team A']);
    $teamB = Team::factory()->create(['name' => 'Team B']);

    $teamA->users()->attach($user);

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/teams');

    $response->assertOk();

    $teamIds = collect($response->json('data'))->pluck('id');

    expect($teamIds)->toContain($teamA->id);
    expect($teamIds)->not()->toContain($teamB->id);
});

it('can silence a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user);

    Sanctum::actingAs($user);

    $silenceUntil = now()->addDay();

    $response = postJson("/api/v1/teams/{$team->id}/silence", [
        'silenced_until' => $silenceUntil->toISOString(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.is_silenced', true)
        ->assertJsonPath('data.alerts_silenced_until', $silenceUntil->copy()->setTimezone('UTC')->toIso8601String());
});
