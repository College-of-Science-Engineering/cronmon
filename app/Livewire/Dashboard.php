<?php

namespace App\Livewire;

use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $teamIds = auth()->user()->teams()->pluck('teams.id');

        // Get counts by status
        $statusCounts = ScheduledTask::whereIn('team_id', $teamIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get recent alerts (last 10)
        $recentAlerts = Alert::whereHas('scheduledTask', function ($query) use ($teamIds) {
            $query->whereIn('team_id', $teamIds);
        })
            ->with(['scheduledTask'])
            ->latest('triggered_at')
            ->limit(10)
            ->get();

        // Get recent check-ins (last 10)
        $recentCheckIns = TaskRun::whereHas('scheduledTask', function ($query) use ($teamIds) {
            $query->whereIn('team_id', $teamIds);
        })
            ->with(['scheduledTask'])
            ->latest('checked_in_at')
            ->limit(10)
            ->get();

        return view('livewire.dashboard', [
            'statusCounts' => $statusCounts,
            'okCount' => $statusCounts['ok'] ?? 0,
            'alertingCount' => $statusCounts['alerting'] ?? 0,
            'pendingCount' => $statusCounts['pending'] ?? 0,
            'pausedCount' => $statusCounts['paused'] ?? 0,
            'recentAlerts' => $recentAlerts,
            'recentCheckIns' => $recentCheckIns,
        ]);
    }
}
