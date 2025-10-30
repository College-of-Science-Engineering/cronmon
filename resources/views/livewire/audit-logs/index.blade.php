<div class="space-y-6">
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="xl">Audit Log</flux:heading>
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
            <flux:date-picker
                wire:model.live="dateRange"
                mode="range"
                with-presets
                name="dateRange"
                presets="today yesterday last7Days last30Days thisMonth lastMonth yearToDate allTime"
                :min="$minDate"
            >
                <flux:date-picker.input placeholder="Filter by date range" clearable name="dateRangeInput" />
            </flux:date-picker>

            <div class="sm:w-64">
                <flux:input
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search logs"
                    clearable
                    icon="magnifying-glass"
                />
            </div>
        </div>
    </div>

    @if($logs->isEmpty())
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No audit entries found</flux:heading>
                <flux:text class="mt-2">Try adjusting your filters or check back after more activity.</flux:text>
            </div>
        </flux:card>
    @else
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-16">ID</flux:table.column>
                    <flux:table.column>Message</flux:table.column>
                    <flux:table.column class="w-64">Recorded</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($logs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell>
                                <flux:text variant="strong">#{{ $log->id }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text>{{ $log->message }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-right">
                                    <flux:text variant="strong">{{ $log->created_at->format('Y-m-d H:i:s') }}</flux:text>
                                    <flux:text>{{ $log->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-6">
                <flux:pagination :paginator="$logs" />
            </div>
        </flux:card>
    @endif
</div>
