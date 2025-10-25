<div>
    <div class="mb-6">
        <flux:heading size="xl">Edit Scheduled Task</flux:heading>
        <flux:text>Update task settings.</flux:text>
    </div>

    <flux:card class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            @include('livewire.scheduled-tasks.partials.task-form-fields', [
                'submitText' => 'Update Task',
                'cancelRoute' => route('tasks.show', $task),
            ])
        </form>
    </flux:card>
</div>
