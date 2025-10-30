<?php

namespace App\Livewire\Teams;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public bool $showAllPersonalTeams = false;

    #[Layout('components.layouts.app')]
    public function render()
    {
        $currentUserId = auth()->id();
        
        $teams = \App\Models\Team::query()
            ->withCount(['users', 'scheduledTasks'])
            ->when(!$this->showAllPersonalTeams, function ($query) use ($currentUserId) {
                // Show only current user's personal team and non-personal teams
                $query->where(function ($q) use ($currentUserId) {
                    $q->whereNull('user_id')
                      ->orWhere('user_id', $currentUserId);
                });
            })
            ->latest()
            ->get();

        return view('livewire.teams.index', [
            'teams' => $teams,
        ]);
    }
}
