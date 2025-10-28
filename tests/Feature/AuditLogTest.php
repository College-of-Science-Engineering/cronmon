<?php

use App\Events\SomethingNoteworthyHappened;
use App\Listeners\RecordAuditLog;
use App\Models\AuditLog;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues the audit log listener when something noteworthy happens', function () {
    Queue::fake();

    SomethingNoteworthyHappened::dispatch('Jenny did something important');

    Queue::assertPushed(
        CallQueuedListener::class,
        function (CallQueuedListener $job): bool {
            return $job->class === RecordAuditLog::class;
        }
    );
});

it('records audit log entries when the listener handles the event', function () {
    $now = Carbon::parse('2024-01-01 12:00:00');
    Carbon::setTestNow($now);

    $listener = new RecordAuditLog;
    $listener->handle(new SomethingNoteworthyHappened('Jenny did something'));

    Carbon::setTestNow();

    $log = AuditLog::first();

    expect($log)->not->toBeNull()
        ->and($log->message)->toBe('Jenny did something')
        ->and($log->created_at->equalTo($now))->toBeTrue();
});
