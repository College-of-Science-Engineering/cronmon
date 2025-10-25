<?php

namespace App\Livewire\ScheduledTasks;

use App\Models\ScheduledTask;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function delete(ScheduledTask $task): void
    {
        $task->delete();

        $this->dispatch('task-deleted');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $teamIds = auth()->user()->teams()->pluck('teams.id');

        $tasks = ScheduledTask::whereIn('team_id', $teamIds)
            ->with(['team', 'creator'])
            ->latest()
            ->get();

        return view('livewire.scheduled-tasks.index', [
            'tasks' => $tasks,
        ]);
    }
}
