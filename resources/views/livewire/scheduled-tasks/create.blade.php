<div>
    <div class="mb-6">
        <flux:heading size="xl">Create Scheduled Task</flux:heading>
        <flux:text>Set up a new task to monitor.</flux:text>
    </div>

    <flux:card class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            @include('livewire.scheduled-tasks.partials.task-form-fields', [
                'submitText' => 'Create Task',
                'cancelRoute' => route('tasks.index'),
            ])
        </form>
    </flux:card>
</div>
