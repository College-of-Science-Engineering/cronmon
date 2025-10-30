<?php

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use Flux\DateRange;
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

    public ?DateRange $dateRange = null;

    protected int $perPage = 100;

    public function mount(): void
    {
        $rangeData = request()->query('range');
        
        if (is_array($rangeData)) {
            $preset = $rangeData['preset'] ?? null;
            
            if ($preset) {
                if ($preset === 'allTime') {
                    $this->dateRange = DateRange::allTime($rangeData['start'] ?? null);
                } else {
                    $this->dateRange = DateRange::fromPreset(\Flux\DateRangePreset::from($preset));
                }
            } elseif (isset($rangeData['start']) || isset($rangeData['end'])) {
                $this->dateRange = new DateRange($rangeData['start'] ?? null, $rangeData['end'] ?? null);
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateRange(): void
    {
        $this->resetPage();
    }

    public function getQueryString(): array
    {
        $params = ['q' => ['as' => 'q', 'except' => '']];
        
        if ($this->dateRange) {
            $rangeData = [
                'start' => $this->dateRange->start()?->format('Y-m-d'),
                'end' => $this->dateRange->end()?->format('Y-m-d'),
            ];
            
            if ($preset = $this->dateRange->preset()) {
                $rangeData['preset'] = $preset->value;
            }
            
            $params['range'] = ['as' => 'range', 'except' => null];
            request()->merge(['range' => $rangeData]);
        }
        
        return $params;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $logs = AuditLog::query()
            ->when($this->search, function ($query) {
                $query->where('message', 'like', '%'.$this->search.'%');
            })
            ->when($this->dateRange, function ($query) {
                $query->whereBetween('created_at', $this->dateRange);
            })
            ->latest()
            ->paginate(perPage: $this->perPage)
            ->withQueryString();

        return view('livewire.audit-logs.index', [
            'logs' => $logs,
            'minDate' => self::MIN_DATE,
        ]);
    }
}
