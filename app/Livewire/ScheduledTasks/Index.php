<?php

namespace App\Livewire\ScheduledTasks;

use App\Models\ScheduledTask;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public ?string $status = null;

    #[Url]
    public ?int $team_id = null;

    public function delete(ScheduledTask $task): void
    {
        $task->delete();

        $this->dispatch('task-deleted');
    }

    public function clearFilter(): void
    {
        $this->status = null;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $teamIds = auth()->user()->teams()->pluck('teams.id');

        $query = ScheduledTask::whereIn('team_id', $teamIds)
            ->with(['team', 'creator'])
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->team_id, function ($query) {
                $query->where('team_id', $this->team_id);
            });

        $tasks = $query->latest()->get();

        return view('livewire.scheduled-tasks.index', [
            'tasks' => $tasks,
            'teams' => auth()->user()->teams,
        ]);
    }
}
