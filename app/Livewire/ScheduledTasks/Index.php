<?php

namespace App\Livewire\ScheduledTasks;

use App\Events\SomethingNoteworthyHappened;
use App\Models\ScheduledTask;
use App\Models\Team;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public ?string $status = null;

    #[Url]
    public ?int $team_id = null;

    #[Url]
    public bool $myTasksOnly = false;

    protected int $perPage = 25;

    public function delete(ScheduledTask $task): void
    {
        $task->delete();

        $actingUser = auth()->user();
        $teamName = $task->team()->value('name');

        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} deleted scheduled task {$task->name} from team {$teamName}");

        $this->dispatch('task-deleted');
    }

    public function clearFilter(): void
    {
        $this->status = null;
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedTeamId(): void
    {
        $this->resetPage();
    }

    public function updatedMyTasksOnly(): void
    {
        $this->resetPage();
    }

    #[On('task-saved')]
    public function refreshTasks(): void
    {
        // Component will automatically re-render
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $tasks = ScheduledTask::with(['team', 'creator'])
            ->when($this->myTasksOnly, function ($query) {
                $query->where('team_id', auth()->user()->personal_team_id);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->team_id, function ($query) {
                $query->where('team_id', $this->team_id);
            })
            ->latest('last_checked_in_at')
            ->paginate(perPage: $this->perPage)
            ->withQueryString();

        return view('livewire.scheduled-tasks.index', [
            'tasks' => $tasks,
            'teams' => Team::orderBy('name')->get(),
        ]);
    }
}
