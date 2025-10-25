<?php

use App\Jobs\RecordTaskCheckIn;
use App\Models\ScheduledTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('returns 404 when token is unknown', function () {
    $this->get(route('api.ping', 'missing-token'))->assertNotFound();
});

it('dispatches the record check-in job for a valid ping', function () {
    $task = ScheduledTask::factory()->create([
        'unique_check_in_token' => 'valid-token',
    ]);

    Queue::fake();

    $payload = [
        'data' => [
            'duration' => 42,
            'notes' => 'all good',
        ],
    ];

    $this->postJson(route('api.ping', 'valid-token'), $payload)
        ->assertOk()
        ->assertJson([
            'message' => 'Check-in recorded',
        ]);

    Queue::assertPushed(RecordTaskCheckIn::class, function (RecordTaskCheckIn $job) use ($task, $payload) {
        return $job->task->is($task)
            && $job->data === $payload['data'];
    });

    Queue::assertPushed(RecordTaskCheckIn::class, 1);
});

it('accepts json provided as a string payload', function () {
    $task = ScheduledTask::factory()->create([
        'unique_check_in_token' => 'string-token',
    ]);

    Queue::fake();

    $this->post(route('api.ping', 'string-token'), [
        'data' => json_encode(['status' => 'ok']),
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'Check-in recorded',
        ]);

    Queue::assertPushed(RecordTaskCheckIn::class, function (RecordTaskCheckIn $job) {
        return $job->data === ['status' => 'ok'];
    });
});

it('rejects invalid json payloads', function () {
    $task = ScheduledTask::factory()->create([
        'unique_check_in_token' => 'invalid-json',
    ]);

    Queue::fake();

    $this->post(route('api.ping', 'invalid-json'), [
        'data' => '{"status": "missing brace"',
    ], [
        'Accept' => 'application/json',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['data']);

    Queue::assertNothingPushed();
});

it('rejects payloads larger than two kilobytes', function () {
    $task = ScheduledTask::factory()->create([
        'unique_check_in_token' => 'too-big',
    ]);

    Queue::fake();

    $oversized = str_repeat('x', 2050);

    $this->postJson(route('api.ping', 'too-big'), [
        'data' => [
            'log' => $oversized,
        ],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['data']);

    Queue::assertNothingPushed();
});
