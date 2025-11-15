<?php

namespace App\Livewire\ImportExport;

use App\Services\ImportPreview;
use App\Services\ImportService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public ?ImportPreview $preview = null;

    public ?array $importData = null;

    public ?string $errorMessage = null;

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.import-export.import');
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:json|max:10240',
        ]);

        try {
            $contents = file_get_contents($this->file->getRealPath());
            $data = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errorMessage = 'Invalid JSON file: '.json_last_error_msg();
                $this->preview = null;
                $this->importData = null;

                return;
            }

            $importService = new ImportService;
            $validation = $importService->validate($data);

            if (! $validation->valid) {
                $this->errorMessage = 'Validation failed: '.implode(', ', $validation->errors);
                $this->preview = null;
                $this->importData = null;

                return;
            }

            $this->preview = $importService->preview($data);
            $this->importData = $data;
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error reading file: '.$e->getMessage();
            $this->preview = null;
            $this->importData = null;
        }
    }

    public function confirmImport()
    {
        if (! $this->importData) {
            $this->errorMessage = 'No import data available';

            return;
        }

        $importService = new ImportService;
        $result = $importService->execute($this->importData);

        if ($result->success) {
            session()->flash('message', sprintf(
                'Import completed! Users: %d created, %d updated. Teams: %d created, %d updated. Tasks: %d created, %d updated.',
                $result->usersCreated,
                $result->usersUpdated,
                $result->teamsCreated,
                $result->teamsUpdated,
                $result->tasksCreated,
                $result->tasksUpdated
            ));

            $this->reset(['file', 'preview', 'importData', 'errorMessage']);
            $this->redirect(route('import-export.index'));
        } else {
            $this->errorMessage = 'Import failed: '.implode(', ', $result->errors);
        }
    }

    public function cancel()
    {
        $this->reset(['file', 'preview', 'importData', 'errorMessage']);
    }
}
