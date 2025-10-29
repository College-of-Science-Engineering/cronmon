<?php

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public ?string $search = null;

    public array $dateRange = [];

    protected int $perPage = 100;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateRange(): void
    {
        $this->resetPage();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        [$startDate, $endDate] = $this->parseDateRange($this->dateRange);

        $logs = AuditLog::query()
            ->when($this->search, function ($query) {
                $query->where('message', 'like', '%'.$this->search.'%');
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('created_at', '<=', $endDate);
            })
            ->latest()
            ->paginate(perPage: $this->perPage)
            ->withQueryString();

        return view('livewire.audit-logs.index', [
            'logs' => $logs,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    private function parseDateRange(array|string|null $range): array
    {
        if (! $range) {
            return [null, null];
        }

        if (is_array($range)) {
            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;
        } else {
            [$start, $end] = array_pad(explode('/', $range, 2), 2, null);
        }

        $startDate = $this->parseDate($start, fn (Carbon $date) => $date->startOfDay());
        $endDate = $this->parseDate($end, fn (Carbon $date) => $date->endOfDay());

        if ($startDate && $endDate && $endDate->lt($startDate)) {
            return [null, null];
        }

        return [$startDate, $endDate];
    }

    private function parseDate(mixed $date, callable $modifier): ?Carbon
    {
        if ($date instanceof \DateTimeInterface) {
            $carbon = Carbon::make($date)?->copy();

            return $carbon ? $modifier($carbon) : null;
        }

        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', trim($date));
        } catch (\Throwable) {
            return null;
        }

        return $modifier($parsed);
    }
}
