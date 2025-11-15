<div>
    <div class="mb-6">
        <flux:heading size="xl">Import / Export</flux:heading>
        <flux:subheading class="mt-2">
            Export your data to move between environments, or import data from another instance.
        </flux:subheading>
    </div>

    @if (session('message'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Export Card --}}
        <flux:card>
            <div class="text-center py-8">
                <flux:icon.arrow-down-tray class="w-16 h-16 mx-auto mb-4 text-blue-500" />
                <flux:heading size="lg" class="mb-2">Export Data</flux:heading>
                <flux:text class="mb-6">
                    Download all your teams and scheduled tasks as a JSON file.
                </flux:text>
                <flux:button href="{{ route('import-export.export') }}" wire:navigate variant="primary">
                    Go to Export
                </flux:button>
            </div>
        </flux:card>

        {{-- Import Card --}}
        <flux:card>
            <div class="text-center py-8">
                <flux:icon.arrow-up-tray class="w-16 h-16 mx-auto mb-4 text-green-500" />
                <flux:heading size="lg" class="mb-2">Import Data</flux:heading>
                <flux:text class="mb-6">
                    Upload a JSON export file to restore teams and tasks.
                </flux:text>
                <flux:button href="{{ route('import-export.import') }}" wire:navigate variant="primary">
                    Go to Import
                </flux:button>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <flux:heading size="sm" class="mb-3">Important Notes</flux:heading>
        <ul class="list-disc list-inside space-y-2 text-sm">
            <li>Exports include all teams and tasks you have access to</li>
            <li>User accounts must exist in the target environment (matched by username)</li>
            <li>Import will update existing teams/tasks or create new ones</li>
            <li>Check-in tokens will be regenerated - you'll need to update your cron jobs</li>
            <li>Task run history and alerts are not included in exports</li>
        </ul>
    </flux:card>
</div>
