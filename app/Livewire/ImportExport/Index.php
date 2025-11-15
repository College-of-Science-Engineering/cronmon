<?php

namespace App\Livewire\ImportExport;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.import-export.index');
    }
}
