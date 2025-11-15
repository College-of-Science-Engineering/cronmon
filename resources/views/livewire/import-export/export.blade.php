<div>
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            <flux:button href="{{ route('import-export.index') }}" wire:navigate icon="arrow-left" variant="ghost">
                Back
            </flux:button>
        </div>
        <flux:heading size="xl">Export Data</flux:heading>
        <flux:subheading class="mt-2">
            Download all your teams and scheduled tasks as a JSON file.
        </flux:subheading>
    </div>

    <flux:card>
        <div class="text-center py-12">
            <flux:icon.arrow-down-tray class="w-20 h-20 mx-auto mb-6 text-blue-500" />
            
            <flux:heading size="lg" class="mb-4">Ready to Export</flux:heading>
            
            <div class="mb-8 space-y-2">
                <flux:text>This export will include:</flux:text>
                <div class="flex justify-center gap-8 mt-4">
                    <div>
                        <div class="text-3xl font-bold text-blue-600">{{ $teamCount }}</div>
                        <flux:text>{{ Str::plural('Team', $teamCount) }}</flux:text>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-blue-600">{{ $taskCount }}</div>
                        <flux:text>{{ Str::plural('Task', $taskCount) }}</flux:text>
                    </div>
                </div>
            </div>

            <flux:button wire:click="download" variant="primary" icon="arrow-down-tray">
                Download Export File
            </flux:button>

            <flux:text class="mt-6 text-sm">
                The file will be downloaded as cronmon-export-YYYY-MM-DD-HHMMSS.json
            </flux:text>
        </div>
    </flux:card>

    <flux:card class="mt-6">
        <flux:heading size="sm" class="mb-3">What's Included</flux:heading>
        <ul class="list-disc list-inside space-y-2 text-sm">
            <li><strong>Users:</strong> All user accounts with their details (username, email, names, roles)</li>
            <li><strong>Teams:</strong> All teams you're a member of (personal and shared)</li>
            <li><strong>Team Members:</strong> Username list for each team</li>
            <li><strong>Scheduled Tasks:</strong> All task configurations including schedules and grace periods</li>
            <li><strong>Metadata:</strong> Task descriptions, timezones, and creator information</li>
        </ul>
    </flux:card>

    <flux:card class="mt-6">
        <flux:heading size="sm" class="mb-3">What's NOT Included</flux:heading>
        <ul class="list-disc list-inside space-y-2 text-sm">
            <li><strong>Passwords:</strong> Random passwords are set on import - users will use SSO to log in</li>
            <li><strong>Task Run History:</strong> Historical check-in data is not exported</li>
            <li><strong>Alerts:</strong> Alert history is not exported</li>
            <li><strong>Check-in Tokens:</strong> New tokens will be generated on import</li>
            <li><strong>Audit Logs:</strong> Audit trail is environment-specific</li>
        </ul>
    </flux:card>
</div>
