<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">Teams</flux:heading>
            <flux:button :href="route('teams.create')" wire:navigate icon="plus">
                Create Team
            </flux:button>
        </div>
    </div>

    @if($teams->isEmpty())
        <flux:card>
            <div class="text-center py-12">
                <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No teams yet</flux:heading>
                <flux:text class="mt-2">Create your first team to get started.</flux:text>
                <flux:button :href="route('teams.create')" wire:navigate class="mt-6" icon="plus">
                    Create Team
                </flux:button>
            </div>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Members</flux:table.column>
                <flux:table.column>Tasks</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($teams as $team)
                    <flux:table.row :key="$team->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:link :href="route('teams.show', $team)" wire:navigate class="font-medium">
                                    {{ $team->name }}
                                </flux:link>
                                @if($team->id === auth()->user()->personal_team_id)
                                    <flux:badge color="zinc" size="sm">Personal</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">
                                {{ $team->users_count }} {{ Str::plural('member', $team->users_count) }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">
                                {{ $team->scheduled_tasks_count }} {{ Str::plural('task', $team->scheduled_tasks_count) }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2 justify-end">
                                <flux:button :href="route('teams.show', $team)" wire:navigate size="sm" variant="ghost" icon="eye" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
