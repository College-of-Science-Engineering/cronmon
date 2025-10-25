<?php

namespace App\Jobs;

use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordTaskCheckIn implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ScheduledTask $task,
        public ?array $data = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Create TaskRun record
        TaskRun::create([
            'scheduled_task_id' => $this->task->id,
            'checked_in_at' => now(),
            'expected_at' => null, // Will be calculated later by background job
            'was_late' => false, // Will be determined later by background job
            'lateness_minutes' => null,
            'data' => $this->data,
        ]);

        // Update task's last check-in time
        $this->task->update([
            'last_checked_in_at' => now(),
            'status' => 'ok',
        ]);
    }
}
