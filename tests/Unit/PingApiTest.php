<?php

use App\Jobs\RecordTaskCheckIn;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('record task check-in job stores task run and updates task state', function () {
    Carbon::setTestNow('2025-01-01 12:00:00');

    $task = ScheduledTask::factory()->create([
        'status' => 'pending',
        'last_checked_in_at' => null,
    ]);

    $job = new RecordTaskCheckIn($task, ['duration' => 90]);

    $job->handle();

    $run = TaskRun::firstWhere('scheduled_task_id', $task->id);

    expect($run)->not->toBeNull();
    expect($run->checked_in_at)->toEqual(Carbon::now());
    expect($run->data)->toBe(['duration' => 90]);

    $task->refresh();

    expect($task->last_checked_in_at)->toEqual(Carbon::now());
    expect($task->status)->toBe('ok');

    Carbon::setTestNow();
});
