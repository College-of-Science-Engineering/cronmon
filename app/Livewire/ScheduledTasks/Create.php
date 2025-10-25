<?php

namespace App\Livewire\ScheduledTasks;

use App\Livewire\Forms\ScheduledTaskForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Create extends Component
{
    public ScheduledTaskForm $form;

    public function save(): void
    {
        $task = $this->form->save();

        $this->redirect(route('tasks.show', $task), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.scheduled-tasks.create');
    }
}
