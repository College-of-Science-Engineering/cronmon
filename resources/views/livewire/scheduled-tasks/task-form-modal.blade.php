<flux:modal name="task-form" variant="flyout" wire:model="showModal">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $taskId ? 'Edit' : 'Create' }} Scheduled Task</flux:heading>
            <flux:text class="mt-2">{{ $taskId ? 'Update task settings.' : 'Set up a new task to monitor.' }}</flux:text>
        </div>

        <flux:field>
            <flux:label>Team</flux:label>
            <flux:select wire:model="team_id" :disabled="$taskId !== null">
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="team_id" />
            @if($taskId)
                <flux:description>Team cannot be changed after creation</flux:description>
            @endif
        </flux:field>

        <flux:field>
            <flux:label>Task Name</flux:label>
            <flux:input wire:model="form.name" placeholder="e.g., Database Backup" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>Description</flux:label>
            <flux:textarea wire:model="form.description" placeholder="Optional notes about this task" rows="3" />
            <flux:error name="form.description" />
        </flux:field>

        <flux:field>
            <flux:label>Schedule Type</flux:label>
            <flux:radio.group wire:model.live="form.schedule_type">
                <flux:radio value="simple" label="Simple interval (e.g., every 5 minutes)" />
                <flux:radio value="cron" label="Cron expression (advanced)" />
            </flux:radio.group>
            <flux:error name="form.schedule_type" />
        </flux:field>

        <flux:field>
            <flux:label>Schedule</flux:label>
            @if($form->schedule_type === 'simple')
                <flux:select wire:model="form.schedule_value">
                    <option value="">Select interval...</option>
                    <option value="5m">Every 5 minutes</option>
                    <option value="15m">Every 15 minutes</option>
                    <option value="30m">Every 30 minutes</option>
                    <option value="1h">Every hour</option>
                    <option value="6h">Every 6 hours</option>
                    <option value="12h">Every 12 hours</option>
                    <option value="daily">Daily</option>
                </flux:select>
            @else
                <flux:input wire:model="form.schedule_value" placeholder="0 3 * * *" />
                <flux:description>
                    Use standard cron syntax. Example: <code class="text-xs">0 3 * * *</code> runs daily at 3am
                </flux:description>
            @endif
            <flux:error name="form.schedule_value" />
        </flux:field>

        <flux:field>
            <flux:label>Timezone</flux:label>
            <flux:select wire:model="form.timezone">
                <option value="UTC">UTC</option>
                <option value="America/New_York">America/New York</option>
                <option value="America/Chicago">America/Chicago</option>
                <option value="America/Los_Angeles">America/Los Angeles</option>
                <option value="Europe/London">Europe/London</option>
                <option value="Europe/Paris">Europe/Paris</option>
                <option value="Asia/Tokyo">Asia/Tokyo</option>
                <option value="Australia/Sydney">Australia/Sydney</option>
            </flux:select>
            <flux:error name="form.timezone" />
        </flux:field>

        <flux:field>
            <flux:label>Grace Period (minutes)</flux:label>
            <flux:input type="number" wire:model="form.grace_period_minutes" min="1" max="1440" />
            <flux:description>
                How many minutes late before alerting (allows for normal variance in run times)
            </flux:description>
            <flux:error name="form.grace_period_minutes" />
        </flux:field>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ $taskId ? 'Update Task' : 'Create Task' }}</flux:button>
            <flux:button type="button" wire:click="showModal = false" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</flux:modal>
