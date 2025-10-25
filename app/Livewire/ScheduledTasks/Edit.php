<?php

namespace App\Livewire\ScheduledTasks;

use App\Livewire\Forms\ScheduledTaskForm;
use App\Models\ScheduledTask;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Edit extends Component
{
    public ScheduledTaskForm $form;

    public ScheduledTask $task;

    public function mount(ScheduledTask $task): void
    {
        $this->task = $task;
        $this->form->setScheduledTask($task);
    }

    public function save(): void
    {
        $this->form->save();

        $this->redirect(route('tasks.show', $this->task), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.scheduled-tasks.edit');
    }
}
