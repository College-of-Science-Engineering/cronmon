<?php

namespace App\Livewire\ImportExport;

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Services\ExportService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Export extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $teamCount = Team::count();
        $taskCount = ScheduledTask::count();

        return view('livewire.import-export.export', [
            'teamCount' => $teamCount,
            'taskCount' => $taskCount,
        ]);
    }

    public function download(): StreamedResponse
    {
        $exportService = new ExportService;
        $data = $exportService->export();

        $filename = 'cronmon-export-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
