<?php

namespace App\Livewire\ScheduledTasks;

use App\Livewire\Forms\ScheduledTaskForm;
use App\Models\ScheduledTask;
use App\Models\Team;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskFormModal extends Component
{
    public ScheduledTaskForm $form;

    public bool $showModal = false;

    public ?int $taskId = null;

    public int $team_id;

    public function mount(): void
    {
        $this->team_id = auth()->user()->personalTeam()->id;
    }

    #[On('open-task-form')]
    public function openModal(?int $taskId = null): void
    {
        $this->taskId = $taskId;

        if ($taskId) {
            $task = ScheduledTask::findOrFail($taskId);
            $this->authorize('update', $task);
            $this->form->setScheduledTask($task);
            $this->team_id = $task->team_id;
        } else {
            $this->form->reset();
            $this->team_id = auth()->user()->personalTeam()->id;
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->taskId) {
            $task = $this->form->save($this->team_id);
        } else {
            $task = $this->form->save($this->team_id);
        }

        $this->showModal = false;
        $this->dispatch('task-saved');

        $this->redirect(route('tasks.show', $task), navigate: true);
    }

    public function render()
    {
        return view('livewire.scheduled-tasks.task-form-modal', [
            'teams' => Team::orderBy('name')->get(),
        ]);
    }
}
