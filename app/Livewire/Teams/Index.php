<?php

namespace App\Livewire\Teams;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $teams = auth()->user()->teams()
            ->withCount(['users', 'scheduledTasks'])
            ->latest()
            ->get();

        return view('livewire.teams.index', [
            'teams' => $teams,
        ]);
    }
}
