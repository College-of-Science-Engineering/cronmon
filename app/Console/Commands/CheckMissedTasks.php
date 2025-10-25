<?php

namespace App\Console\Commands;

use App\Mail\TaskMissedNotification;
use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Services\ScheduleCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckMissedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for tasks that have missed their schedule and send alerts';

    public function __construct(protected ScheduleCalculator $calculator)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tasks = ScheduledTask::with('team.users')
            ->whereNotIn('status', ['paused'])
            ->get();

        foreach ($tasks as $task) {
            $this->checkTask($task);
        }

        return self::SUCCESS;
    }

    protected function checkTask(ScheduledTask $task): void
    {
        // Skip pending tasks that have never checked in
        if ($task->status === 'pending' && $task->last_checked_in_at === null) {
            return;
        }

        $isLate = $this->calculator->isTaskLate(
            $task->schedule_type,
            $task->schedule_value,
            $task->last_checked_in_at,
            $task->grace_period_minutes,
            now()
        );

        if ($isLate && $task->status !== 'alerting') {
            $this->createMissedAlert($task);
        } elseif (! $isLate && $task->status === 'alerting') {
            $this->createRecoveryAlert($task);
        }
    }

    protected function createMissedAlert(ScheduledTask $task): void
    {
        // Create alert
        $alert = Alert::create([
            'scheduled_task_id' => $task->id,
            'alert_type' => 'missed',
            'triggered_at' => now(),
            'message' => "Task '{$task->name}' has missed its scheduled run and exceeded the grace period.",
        ]);

        // Update task status
        $task->update(['status' => 'alerting']);

        // Send email to all team members
        foreach ($task->team->users as $user) {
            Mail::to($user->email)->send(new TaskMissedNotification($task, $alert));
        }
    }

    protected function createRecoveryAlert(ScheduledTask $task): void
    {
        // Create recovery alert
        $alert = Alert::create([
            'scheduled_task_id' => $task->id,
            'alert_type' => 'recovered',
            'triggered_at' => now(),
            'message' => "Task '{$task->name}' has recovered and is checking in again.",
        ]);

        // Update task status
        $task->update(['status' => 'ok']);

        // Send email to all team members
        foreach ($task->team->users as $user) {
            Mail::to($user->email)->send(new TaskMissedNotification($task, $alert));
        }
    }
}
