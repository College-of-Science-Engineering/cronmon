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
            ->reverse(); // Reverse to get chronological order (oldest to newest)

        if ($runs->isEmpty()) {
            return [];
        }

        // Flux charts expect an array of objects with named fields
        return $runs->map(function ($run) {
            return [
                'date' => $run->checked_in_at->format('M j'),
                'execution_time' => $run->execution_time_seconds ?? ($run->data['execution_time'] ?? 0),
            ];
        })->toArray();
    }
}
