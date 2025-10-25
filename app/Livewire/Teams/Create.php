<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    public function save(): void
    {
        $this->validate();

        $slug = Str::slug($this->name);
        $originalSlug = $slug;
        $counter = 1;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $team = Team::create([
            'name' => $this->name,
            'slug' => $slug,
        ]);

        $team->users()->attach(auth()->id());

        $this->redirect(route('teams.show', $team), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.teams.create');
    }
}
