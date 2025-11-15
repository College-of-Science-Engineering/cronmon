<div>
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            <flux:button href="{{ route('import-export.index') }}" wire:navigate icon="arrow-left" variant="ghost">
                Back
            </flux:button>
        </div>
        <flux:heading size="xl">Import Data</flux:heading>
        <flux:subheading class="mt-2">
            Upload a JSON export file to restore teams and tasks.
        </flux:subheading>
    </div>

    @if ($errorMessage)
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
            {{ $errorMessage }}
        </div>
    @endif

    @if (!$preview)
        {{-- Upload Form --}}
        <flux:card>
            <div class="py-12">
                <flux:icon.arrow-up-tray class="w-20 h-20 mx-auto mb-6 text-green-500" />
                
                <flux:heading size="lg" class="mb-4 text-center">Select Import File</flux:heading>
                
                <div class="max-w-md mx-auto">
                    <flux:input 
                        type="file" 
                        wire:model="file" 
                        accept=".json,application/json"
                        label="JSON Export File"
                    />
                    <flux:text class="mt-2 text-sm">
                        Select a cronmon-export-*.json file to import
                    </flux:text>

                    <div wire:loading wire:target="file" class="mt-4 text-center">
                        <flux:text>Processing file...</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card class="mt-6">
            <flux:heading size="sm" class="mb-3">Before You Import</flux:heading>
            <ul class="list-disc list-inside space-y-2 text-sm">
                <li>Ensure all users in the export exist in this environment (matched by username)</li>
                <li>Existing teams and tasks will be updated with imported values</li>
                <li>New check-in tokens will be generated - update your cron jobs afterwards</li>
                <li>The import is wrapped in a transaction - it either completes fully or rolls back</li>
            </ul>
        </flux:card>
    @else
        {{-- Preview --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Import Preview</flux:heading>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <flux:heading size="sm" class="mb-2">Users</flux:heading>
                    <div class="flex gap-4">
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ count($preview->usersToCreate) }}</div>
                            <flux:text class="text-sm">To Create</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">{{ count($preview->usersToUpdate) }}</div>
                            <flux:text class="text-sm">To Update</flux:text>
                        </div>
                    </div>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Teams</flux:heading>
                    <div class="flex gap-4">
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ count($preview->teamsToCreate) }}</div>
                            <flux:text class="text-sm">To Create</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">{{ count($preview->teamsToUpdate) }}</div>
                            <flux:text class="text-sm">To Update</flux:text>
                        </div>
                    </div>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Tasks</flux:heading>
                    <div class="flex gap-4">
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ count($preview->tasksToCreate) }}</div>
                            <flux:text class="text-sm">To Create</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">{{ count($preview->tasksToUpdate) }}</div>
                            <flux:text class="text-sm">To Update</flux:text>
                        </div>
                    </div>
                </div>
            </div>

            @if (count($preview->warnings) > 0)
                <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg">
                    <strong>Warnings:</strong>
                    <ul class="list-disc list-inside mt-2">
                        @foreach ($preview->warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($preview->teamsToCreate) > 0)
                <div class="mb-4">
                    <flux:heading size="sm" class="mb-2">Teams to Create</flux:heading>
                    <flux:card variant="outline">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($preview->teamsToCreate as $teamName)
                                <li>{{ $teamName }}</li>
                            @endforeach
                        </ul>
                    </flux:card>
                </div>
            @endif

            @if (count($preview->teamsToUpdate) > 0)
                <div class="mb-4">
                    <flux:heading size="sm" class="mb-2">Teams to Update</flux:heading>
                    <flux:card variant="outline">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($preview->teamsToUpdate as $teamName)
                                <li>{{ $teamName }}</li>
                            @endforeach
                        </ul>
                    </flux:card>
                </div>
            @endif

            @if (count($preview->tasksToCreate) > 0)
                <div class="mb-4">
                    <flux:heading size="sm" class="mb-2">Tasks to Create</flux:heading>
                    <flux:card variant="outline">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach (array_slice($preview->tasksToCreate, 0, 10) as $taskName)
                                <li>{{ $taskName }}</li>
                            @endforeach
                            @if (count($preview->tasksToCreate) > 10)
                                <li class="font-semibold">... and {{ count($preview->tasksToCreate) - 10 }} more</li>
                            @endif
                        </ul>
                    </flux:card>
                </div>
            @endif

            @if (count($preview->tasksToUpdate) > 0)
                <div class="mb-4">
                    <flux:heading size="sm" class="mb-2">Tasks to Update</flux:heading>
                    <flux:card variant="outline">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach (array_slice($preview->tasksToUpdate, 0, 10) as $taskName)
                                <li>{{ $taskName }}</li>
                            @endforeach
                            @if (count($preview->tasksToUpdate) > 10)
                                <li class="font-semibold">... and {{ count($preview->tasksToUpdate) - 10 }} more</li>
                            @endif
                        </ul>
                    </flux:card>
                </div>
            @endif

            <div class="flex gap-4 mt-6">
                <flux:button wire:click="confirmImport" variant="primary" icon="check">
                    Confirm Import
                </flux:button>
                <flux:button wire:click="cancel" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>
