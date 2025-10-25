<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">Scheduled Tasks</flux:heading>
            <flux:button :href="route('tasks.create')" wire:navigate icon="plus">
                New Task
            </flux:button>
        </div>

        <div class="mt-4 flex items-center gap-4">
            <span class="">
                <flux:radio.group wire:model.live="status" variant="pills">
                    <flux:radio value="" label="All" />
                    <flux:radio value="alerting" label="Alerting" />
                    <flux:radio value="paused" label="Paused" />
                </flux:radio.group>
            </span>
            <flux:select wire:model.live="team_id" placeholder="All Teams" class="flex-1">
                <flux:select.option value="">All Teams</flux:select.option>
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($tasks->isEmpty())
        <flux:card>
            <div class="text-center py-12">
                <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                @if($status === 'alerting')
                    <flux:heading size="lg" class="mt-4">No alerting tasks</flux:heading>
                    <flux:text class="mt-2">Great! All your tasks are running smoothly.</flux:text>
                @elseif($status === 'paused')
                    <flux:heading size="lg" class="mt-4">No paused tasks</flux:heading>
                    <flux:text class="mt-2">You don't have any paused tasks.</flux:text>
                @else
                    <flux:heading size="lg" class="mt-4">No tasks yet</flux:heading>
                    <flux:text class="mt-2">Get started by creating your first scheduled task.</flux:text>
                    <flux:button :href="route('tasks.create')" wire:navigate class="mt-6" icon="plus">
                        Create Task
                    </flux:button>
                @endif
            </div>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Team</flux:table.column>
                <flux:table.column>Schedule</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Last Check-in</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($tasks as $task)
                    <flux:table.row :key="$task->id">
                        <flux:table.cell>
                            <div>
                                <flux:link :href="route('tasks.show', $task)" wire:navigate class="font-medium">
                                    {{ $task->name }}
                                </flux:link>
                                @if($task->description)
                                    <flux:text class="text-sm">{{ $task->description }}</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge>{{ $task->team->name }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">
                                {{ $task->schedule_type === 'simple' ? $task->schedule_value : 'Cron: ' . $task->schedule_value }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="match($task->status) {
                                'ok' => 'green',
                                'pending' => 'yellow',
                                'alerting' => 'red',
                                'paused' => 'zinc',
                            }">
                                {{ ucfirst($task->status) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">
                                {{ $task->last_checked_in_at?->diffForHumans() ?? 'Never' }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2 justify-end">
                                <flux:button :href="route('tasks.show', $task)" wire:navigate size="sm" variant="ghost" icon="eye" />
                                <flux:button :href="route('tasks.edit', $task)" wire:navigate size="sm" variant="ghost" icon="pencil" />
                                <flux:button wire:click="delete({{ $task->id }})" wire:confirm="Are you sure you want to delete this task?" size="sm" variant="ghost" icon="trash" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
