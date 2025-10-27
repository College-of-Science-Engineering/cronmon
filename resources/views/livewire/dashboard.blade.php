<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:button wire:click="$dispatch('open-task-form')" icon="plus">
                New Task
            </flux:button>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- OK Tasks --}}
        <a href="{{ route('tasks.index', ['status' => 'ok']) }}" wire:navigate class="cursor-pointer">
            <flux:card>
                <div class="text-center">
                    <div class="text-4xl font-bold mt-2">{{ $okCount }}</div>
                    <flux:text class="mt-2">Tasks running smoothly</flux:text>
                </div>
            </flux:card>
        </a>

        {{-- Alerting Tasks --}}
        <a href="{{ route('tasks.index', ['status' => 'alerting']) }}" wire:navigate class="cursor-pointer">
            <flux:card>
                <div class="text-center">
                    <div class="text-4xl font-bold mt-2">{{ $alertingCount }}</div>
                    <flux:text class="mt-2">Tasks need attention</flux:text>
                </div>
            </flux:card>
        </a>

        {{-- Pending Tasks --}}
        <a href="{{ route('tasks.index', ['status' => 'pending']) }}" wire:navigate class="cursor-pointer">
            <flux:card>
                <div class="text-center">
                    <div class="text-4xl font-bold mt-2">{{ $pendingCount }}</div>
                    <flux:text class="mt-2">Never checked in</flux:text>
                </div>
            </flux:card>
        </a>

        {{-- Paused Tasks --}}
        <a href="{{ route('tasks.index', ['status' => 'paused']) }}" wire:navigate class="cursor-pointer">
            <flux:card>
                <div class="text-center">
                    <div class="text-4xl font-bold mt-2">{{ $pausedCount }}</div>
                    <flux:text class="mt-2">Monitoring disabled</flux:text>
                </div>
            </flux:card>
        </a>
    </div>

    {{-- Recent Activity - Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Alerts --}}
        <div>
            <flux:heading size="lg" class="mb-4">Recent Alerts</flux:heading>

            @if($recentAlerts->isEmpty())
                <flux:card>
                    <div class="text-center py-8">
                        <flux:icon.check-circle class="mx-auto h-12 w-12 text-green-400" />
                        <flux:heading size="lg" class="mt-4">All clear!</flux:heading>
                        <flux:text class="mt-2">No recent alerts.</flux:text>
                    </div>
                </flux:card>
            @else
                <flux:card>
                    <div class="space-y-4">
                        @foreach($recentAlerts as $alert)
                            <div class="flex items-start justify-between border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <flux:badge :color="match($alert->alert_type) {
                                            'missed' => 'red',
                                            'late' => 'yellow',
                                            'recovered' => 'green',
                                        }">
                                            {{ ucfirst($alert->alert_type) }}
                                        </flux:badge>
                                        <flux:link :href="route('tasks.show', $alert->scheduledTask)" wire:navigate class="font-medium">
                                            {{ $alert->scheduledTask->name }}
                                        </flux:link>
                                    </div>
                                    <flux:text class="text-sm mt-1">{{ $alert->message }}</flux:text>
                                    <flux:text class="text-xs text-zinc-500 mt-1">{{ $alert->triggered_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Recent Check-ins --}}
        <div>
            <flux:heading size="lg" class="mb-4">Recent Check-ins</flux:heading>

            @if($recentCheckIns->isEmpty())
                <flux:card>
                    <div class="text-center py-8">
                        <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">No check-ins yet</flux:heading>
                        <flux:text class="mt-2">Waiting for tasks to check in.</flux:text>
                    </div>
                </flux:card>
            @else
                <flux:card>
                    <div class="space-y-4">
                        @foreach($recentCheckIns as $checkIn)
                            <div class="flex items-start justify-between border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0">
                                <div class="flex-1">
                                    <flux:link :href="route('tasks.show', $checkIn->scheduledTask)" wire:navigate class="font-medium">
                                        {{ $checkIn->scheduledTask->name }}
                                    </flux:link>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($checkIn->was_late)
                                            <flux:badge color="yellow">Late</flux:badge>
                                            <flux:text class="text-sm">{{ $checkIn->lateness_minutes }} min late</flux:text>
                                        @else
                                            <flux:badge color="green">On time</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text class="text-xs text-zinc-500 mt-1">{{ $checkIn->checked_in_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    <livewire:scheduled-tasks.task-form-modal />
</div>
