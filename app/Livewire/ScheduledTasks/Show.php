<?php

namespace App\Livewire\ScheduledTasks;

use App\Models\ScheduledTask;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public ScheduledTask $task;

    public string $currentTab = 'details';

    public function mount(ScheduledTask $task): void
    {
        $this->task = $task;
    }

    #[On('task-saved')]
    public function refreshTask(): void
    {
        $this->task->refresh();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $this->task->load([
            'team',
            'creator',
            'taskRuns' => fn ($query) => $query->latest()->limit(20),
            'alerts' => fn ($query) => $query->latest()->limit(20),
        ]);

        // Prepare chart data from last 30 task runs
        $chartData = $this->prepareChartData();

        // Get currently running task run (if any)
        $runningTaskRun = $this->task->currentlyRunningTaskRun();

        return view('livewire.scheduled-tasks.show', [
            'chartData' => $chartData,
            'runningTaskRun' => $runningTaskRun,
        ]);
    }

    protected function prepareChartData(): array
    {
        $runs = $this->task->taskRuns()
            ->orderBy('checked_in_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse() // Reverse to get chronological order (oldest to newest)
            ->values(); // Re-index to ensure proper array (not object) for JavaScript

        if ($runs->isEmpty()) {
            return [];
        }

        // Flux charts expect an array of objects with named fields
        // Use run number for X-axis to spread points out, full date/time in tooltip
        return $runs->map(function ($run, $index) {
            return [
                'run_number' => $index + 1,
                'date_time' => $run->checked_in_at->format('M j, Y g:i A'),
                'execution_time' => $run->execution_time_seconds ?? ($run->data['execution_time'] ?? 0),
            ];
        })->toArray();
    }
}
