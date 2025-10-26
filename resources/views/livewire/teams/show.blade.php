<div>
    {{-- Hero Section --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl">{{ $team->name }}</flux:heading>
                    @if($team->isPersonalTeam())
                        <flux:badge color="zinc">Personal</flux:badge>
                    @endif
                </div>
                <flux:text class="text-zinc-500">
                    {{ $team->slug }}
                </flux:text>
            </div>
            <div class="flex gap-2">
                @if(!$team->isPersonalTeam())
                    <flux:button wire:click="deleteTeam" variant="danger" icon="trash">
                        Delete Team
                    </flux:button>
                @endif
                <flux:button :href="route('teams.index')" wire:navigate variant="ghost">
                    Back to Teams
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Members Section --}}
    <div class="mb-6">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Members</flux:heading>

            @if($members->isEmpty())
                <div class="text-center py-8">
                    <flux:icon.users class="mx-auto h-12 w-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">No members yet</flux:heading>
                    <flux:text class="mt-2">Add your first team member to get started.</flux:text>
                </div>
            @else
                <flux:table class="mb-6">
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($members as $member)
                            <flux:table.row :key="$member->id">
                                <flux:table.cell>
                                    <flux:text>{{ $member->full_name }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text class="text-sm">{{ $member->email }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex justify-end">
                                        <flux:button
                                            wire:click="removeMember({{ $member->id }})"
                                            wire:confirm="Are you sure you want to remove this member?"
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                        />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif

            <flux:separator class="my-6" />

            <div>
                <flux:heading size="sm" class="mb-3">Add Member</flux:heading>
                <form wire:submit="addMember" class="flex gap-2">
                    <div class="flex-1">
                        <flux:input wire:model="newMemberEmail" type="email" placeholder="user@example.com" />
                        <flux:error name="newMemberEmail" />
                    </div>
                    <flux:button type="submit">Add</flux:button>
                </form>
            </div>
        </flux:card>
    </div>

    {{-- Tasks Section --}}
    <div class="mb-6">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Scheduled Tasks</flux:heading>

            @if($tasks->isEmpty())
                <div class="text-center py-8">
                    <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">No tasks yet</flux:heading>
                    <flux:text class="mt-2">This team doesn't have any scheduled tasks.</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Schedule</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($tasks as $task)
                            <flux:table.row :key="$task->id">
                                <flux:table.cell>
                                    <flux:link :href="route('tasks.show', $task)" wire:navigate class="font-medium">
                                        {{ $task->name }}
                                    </flux:link>
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
                                    <div class="flex justify-end">
                                        <flux:button :href="route('tasks.show', $task)" wire:navigate size="sm" variant="ghost" icon="eye" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    </div>

    {{-- Migration Modal --}}
    <flux:modal name="migration" wire:model="showMigrationModal" variant="flyout">
        <form wire:submit="confirmMigration" class="space-y-6">
            <div>
                <flux:heading size="lg">Migrate Tasks Before Deleting</flux:heading>
                <flux:text class="mt-2">
                    This team has {{ $team->scheduledTasks()->count() }} scheduled {{ Str::plural('task', $team->scheduledTasks()->count()) }}.
                    Please select a team to migrate them to before deleting this team.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Migrate tasks to</flux:label>
                <flux:select wire:model="migrationTargetTeamId" placeholder="Select a team">
                    @foreach($availableTeams as $availableTeam)
                        <flux:select.option value="{{ $availableTeam->id }}">{{ $availableTeam->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="migrationTargetTeamId" />
            </flux:field>

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="danger">Migrate and Delete Team</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showMigrationModal', false)">Cancel</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
