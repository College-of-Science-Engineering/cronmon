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

    #[Url(as: 'range')]
    public ?DateRange $dateRange = null;

    protected int $perPage = 100;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateRange(): void
    {
        $this->resetPage();
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
