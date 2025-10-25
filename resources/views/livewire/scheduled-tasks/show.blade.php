<div>
    {{-- Hero Section with Task Name and Last Check-in --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ $task->name }}</flux:heading>
                <flux:text class="text-zinc-500">
                    @if($task->description)
                        {{ $task->description }} •
                    @endif
                    Last check-in: {{ $task->last_checked_in_at?->diffForHumans() ?? 'Never' }}
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button wire:click="$dispatch('open-task-form', {taskId: {{ $task->id }}})" icon="pencil">
                    Edit
                </flux:button>
                <flux:button :href="route('tasks.index')" wire:navigate variant="ghost">
                    Back to List
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Prominent Status Callout --}}
    <div class="mb-6">
        <flux:callout :variant="match($task->status) {
            'ok' => 'success',
            'alerting' => 'danger',
            'pending' => 'warning',
            'paused' => 'neutral',
        }">
            <flux:callout.heading>Status</flux:callout.heading>
            <div class="text-4xl font-bold mt-2">
                {{ strtoupper($task->status) }}
            </div>
        </flux:callout>
    </div>

    {{-- Check-in History Chart --}}
    @if(!empty($chartData))
        <div class="mb-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Check-in History (Last 30 Runs)</flux:heading>
                <flux:chart :value="$chartData" class="h-64">
                    <flux:chart.svg>
                        <flux:chart.line field="execution_time" class="text-blue-500 dark:text-blue-400" />
                        <flux:chart.point field="execution_time" class="text-blue-500 dark:text-blue-400" />
                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>
                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" />
                        <flux:chart.tooltip.value field="execution_time" label="Execution Time (s)" />
                    </flux:chart.tooltip>
                </flux:chart>
            </flux:card>
        </div>
    @else
        <div class="mb-6">
            <flux:card>
                <div class="text-center py-12">
                    <flux:icon.chart-bar class="mx-auto h-12 w-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">No Check-in History Yet</flux:heading>
                    <flux:text class="mt-2">This task hasn't checked in yet. Check-in data will appear here once the task starts running.</flux:text>
                </div>
            </flux:card>
        </div>
    @endif

    <flux:tab.group>
        <flux:tabs wire:model="currentTab">
            <flux:tab name="details">Details</flux:tab>
            <flux:tab name="history">History</flux:tab>
            <flux:tab name="alerts">Alerts</flux:tab>
            <flux:tab name="api">API</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <flux:card>
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm">Quick Start</flux:heading>
                        <flux:text class="mt-1 mb-2">Copy and paste this command to start monitoring this task:</flux:text>
                        <flux:input value="curl {{ $task->getPingUrl() }}" readonly copyable />
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Team</flux:heading>
                        <flux:text class="mt-1">{{ $task->team->name }}</flux:text>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Schedule</flux:heading>
                        <flux:text class="mt-1">
                            {{ $task->schedule_type === 'simple' ? $task->schedule_value : 'Cron: ' . $task->schedule_value }}
                        </flux:text>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Timezone</flux:heading>
                        <flux:text class="mt-1">{{ $task->timezone }}</flux:text>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Grace Period</flux:heading>
                        <flux:text class="mt-1">{{ $task->grace_period_minutes }} minutes</flux:text>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Check-in Token</flux:heading>
                        <div class="mt-1 flex items-center gap-2">
                            <code class="text-sm bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $task->unique_check_in_token }}</code>
                            <flux:button size="sm" variant="ghost" icon="clipboard">Copy</flux:button>
                        </div>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">Created By</flux:heading>
                        <flux:text class="mt-1">{{ $task->creator->forenames }} {{ $task->creator->surname }}</flux:text>
                    </div>
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="history">
            @if($task->taskRuns->isEmpty())
                <flux:card>
                    <div class="text-center py-12">
                        <flux:icon.clock class="mx-auto h-12 w-12 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">No check-ins yet</flux:heading>
                        <flux:text class="mt-2">This task hasn't checked in yet.</flux:text>
                    </div>
                </flux:card>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Checked In</flux:table.column>
                        <flux:table.column>Expected At</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Lateness</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($task->taskRuns as $run)
                            <flux:table.row :key="$run->id">
                                <flux:table.cell>
                                    <flux:text>{{ $run->checked_in_at->format('M j, Y g:i A') }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $run->expected_at?->format('M j, Y g:i A') ?? 'N/A' }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$run->was_late ? 'red' : 'green'">
                                        {{ $run->was_late ? 'Late' : 'On Time' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $run->lateness_minutes ? $run->lateness_minutes . ' min' : '-' }}</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="alerts">
            @if($task->alerts->isEmpty())
                <flux:card>
                    <div class="text-center py-12">
                        <flux:icon.bell class="mx-auto h-12 w-12 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">No alerts</flux:heading>
                        <flux:text class="mt-2">No alerts have been triggered for this task.</flux:text>
                    </div>
                </flux:card>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Message</flux:table.column>
                        <flux:table.column>Triggered</flux:table.column>
                        <flux:table.column>Acknowledged</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($task->alerts as $alert)
                            <flux:table.row :key="$alert->id">
                                <flux:table.cell>
                                    <flux:badge :color="match($alert->alert_type) {
                                        'missed' => 'red',
                                        'late' => 'yellow',
                                        'recovered' => 'green',
                                    }">
                                        {{ ucfirst($alert->alert_type) }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $alert->message }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $alert->triggered_at->diffForHumans() }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($alert->acknowledged_at)
                                        <flux:text class="text-sm">{{ $alert->acknowledged_at->diffForHumans() }}</flux:text>
                                    @else
                                        <flux:badge color="zinc">Unacknowledged</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="api">
            <flux:card>
                <div class="space-y-6">
                    <div>
                        <flux:heading size="sm">Basic GET Request</flux:heading>
                        <flux:text class="mt-1 mb-2">Simple check-in with no additional data:</flux:text>
                        <flux:input value="curl {{ $task->getPingUrl() }}" readonly copyable />
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">POST with JSON Data</flux:heading>
                        <flux:text class="mt-1 mb-2">Include custom data with your check-in (e.g., execution time, status, logs):</flux:text>
                        <flux:textarea rows="3" readonly>curl -X POST {{ $task->getPingUrl() }} \
  -H "Content-Type: application/json" \
  -d '{"data":{"execution_time":45,"status":"success"}}'</flux:textarea>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm">From Your Cron Job</flux:heading>
                        <flux:text class="mt-1 mb-2">Add this to the end of your cron job command:</flux:text>
                        <flux:textarea rows="2" readonly>0 3 * * * /path/to/your/script.sh && \
  curl {{ $task->getPingUrl() }}</flux:textarea>
                        <flux:text class="mt-2 text-sm text-zinc-500">The && ensures the ping only happens if your script succeeds (exits with code 0).</flux:text>
                    </div>
                </div>
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>

    <livewire:scheduled-tasks.task-form-modal />
</div>
