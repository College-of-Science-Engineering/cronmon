<?php

namespace App\Livewire\Teams;

use App\Events\SomethingNoteworthyHappened;
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

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $slug = Str::slug($this->name);
        $originalSlug = $slug;
        $counter = 1;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $team = Team::create([
            'name' => $this->name,
            'slug' => $slug,
        ]);

        SomethingNoteworthyHappened::dispatch("{$user->full_name} created team {$team->name}");

        $team->users()->attach($user->getKey());

        SomethingNoteworthyHappened::dispatch("{$user->full_name} joined team {$team->name}");

        $this->redirect(route('teams.show', $team), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.teams.create');
    }
}
