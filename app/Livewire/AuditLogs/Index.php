<?php

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use Flux\DateRange;
use Flux\DateRangePreset;
use Flux\DateRangeSynth;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    private const MIN_DATE = '2025-01-01';

    #[Url(as: 'q')]
    public ?string $search = null;

    #[Url(as: 'range')]
    public ?string $rangeQuery = null;

    public ?DateRange $dateRange = null;

    protected int $perPage = 100;

    public function mount(): void
    {
        $this->dateRange = $this->dateRangeFromQuery($this->rangeQuery);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateRange(?DateRange $range): void
    {
        $queryValue = $this->dateRangeToQuery($range);

        if ($this->rangeQuery !== $queryValue) {
            $this->rangeQuery = $queryValue;
        }

        $this->resetPage();
    }

    public function updatedRangeQuery(?string $value): void
    {
        if ($value === $this->dateRangeToQuery($this->dateRange)) {
            return;
        }

        $this->dateRange = $this->dateRangeFromQuery($value);
        $this->resetPage();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        [$startDate, $endDate] = $this->dateBounds($this->dateRange);

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
            'minDate' => self::MIN_DATE,
        ]);
    }

    private function dateRangeFromQuery(?string $value): ?DateRange
    {
        if ($value === null || $value === '') {
            return null;
        }

        $data = $this->rangeQueryData($value);

        if (! $data) {
            return null;
        }

        return DateRangeSynth::hydrateFromType(DateRange::class, $data);
    }

    private function dateRangeToQuery(?DateRange $range): ?string
    {
        if (! $range) {
            return null;
        }

        $preset = $range->preset();

        if ($preset && $preset !== DateRangePreset::Custom) {
            return $preset->value;
        }

        $start = $range->start()?->format('Y-m-d');
        $end = $range->end()?->format('Y-m-d');

        if (! $start && ! $end) {
            return null;
        }

        return trim("{$start}/{$end}", '/');
    }

    private function rangeQueryData(string $value): ?array
    {
        $preset = DateRangePreset::tryFrom($value);

        if ($preset && $preset !== DateRangePreset::Custom) {
            $data = ['preset' => $preset->value];

            if ($preset === DateRangePreset::AllTime) {
                $data['start'] = self::MIN_DATE;
            }

            return $data;
        }

        [$start, $end] = array_pad(explode('/', $value, 2), 2, null);

        $start = $this->normaliseDateString($start);
        $end = $this->normaliseDateString($end);

        if (! $start && ! $end) {
            return null;
        }

        $data = [];

        if ($start) {
            $data['start'] = $start;
        }

        if ($end) {
            $data['end'] = $end;
        }

        return $data;
    }

    private function normaliseDateString(?string $value): ?string
    {
        $trimmed = $value ? trim($value) : null;

        if (! $trimmed) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $trimmed)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateBounds(?DateRange $range): array
    {
        if (! $range) {
            return [null, null];
        }

        $start = $range->hasStart()
            ? $range->start()?->copy()->startOfDay()
            : null;

        $end = $range->hasEnd()
            ? $range->end()?->copy()->endOfDay()
            : null;

        return [$start, $end];
    }
}
