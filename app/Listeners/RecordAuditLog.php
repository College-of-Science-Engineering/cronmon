<?php

namespace App\Listeners;

use App\Events\SomethingNoteworthyHappened;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

class RecordAuditLog implements ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle(SomethingNoteworthyHappened $event): void
    {
        AuditLog::create([
            'message' => $event->message,
        ]);
    }
}
