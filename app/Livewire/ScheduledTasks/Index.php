<?php

namespace App\Livewire\ScheduledTasks;

use App\Models\ScheduledTask;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public ?string $status = null;

    #[Url]
    public ?int $team_id = null;

    #[Url]
    public bool $myTasksOnly = false;

    public function delete(ScheduledTask $task): void
    {
        $task->delete();

        $this->dispatch('task-deleted');
    }

    public function clearFilter(): void
    {
        $this->status = null;
    }

    #[On('task-saved')]
    public function refreshTasks(): void
    {
        // Component will automatically re-render
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = ScheduledTask::with(['team', 'creator'])
            ->when($this->myTasksOnly, function ($query) {
                $query->where('team_id', auth()->user()->personal_team_id);
            })
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
