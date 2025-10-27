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
        public ?array $data = null,
        public bool $isStart = false,
        public bool $isFinish = false
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->isStart) {
            $this->handleStartPing();
        } elseif ($this->isFinish) {
            $this->handleFinishPing();
        } else {
            $this->handlePlainPing();
        }
    }

    protected function handleStartPing(): void
    {
        // Create TaskRun record with started_at, but don't update task status
        TaskRun::create([
            'scheduled_task_id' => $this->task->id,
            'checked_in_at' => now(),
            'started_at' => now(),
            'finished_at' => null,
            'execution_time_seconds' => null,
            'expected_at' => null,
            'was_late' => false,
            'lateness_minutes' => null,
            'data' => $this->data,
        ]);

        // Do NOT update last_checked_in_at or status - job hasn't finished yet
    }

    protected function handleFinishPing(): void
    {
        // Try to find incomplete TaskRun (one with start but no finish)
        $incompleteRun = $this->task->taskRuns()
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();

        if ($incompleteRun) {
            // Complete the existing run
            $executionSeconds = $incompleteRun->started_at->diffInSeconds(now());

            $incompleteRun->update([
                'finished_at' => now(),
                'execution_time_seconds' => $executionSeconds,
            ]);
        } else {
            // No prior start - create new TaskRun with just finish
            TaskRun::create([
                'scheduled_task_id' => $this->task->id,
                'checked_in_at' => now(),
                'started_at' => null,
                'finished_at' => now(),
                'execution_time_seconds' => null,
                'expected_at' => null,
                'was_late' => false,
                'lateness_minutes' => null,
                'data' => $this->data,
            ]);
        }

        // Update task status - job is complete
        $this->task->update([
            'last_checked_in_at' => now(),
            'status' => 'ok',
        ]);
    }

    protected function handlePlainPing(): void
    {
        // Original behavior - simple check-in
        TaskRun::create([
            'scheduled_task_id' => $this->task->id,
            'checked_in_at' => now(),
            'expected_at' => null,
            'was_late' => false,
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
