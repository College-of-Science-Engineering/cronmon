<div>
    <div class="mb-6">
        <flux:heading size="xl">Create Team</flux:heading>
        <flux:text>Create a new team to organize your scheduled tasks.</flux:text>
    </div>

    <flux:card class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <flux:field>
                <flux:label>Team Name</flux:label>
                <flux:input wire:model="name" placeholder="Engineering Team" />
                <flux:error name="name" />
            </flux:field>

            <div class="flex items-center gap-3">
                <flux:button type="submit">Create Team</flux:button>
                <flux:button variant="ghost" :href="route('teams.index')" wire:navigate>Cancel</flux:button>
            </div>
        </form>
    </flux:card>
</div>
