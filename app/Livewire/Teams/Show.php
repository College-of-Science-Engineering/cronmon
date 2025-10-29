<?php

namespace App\Livewire\Teams;

use App\Events\SomethingNoteworthyHappened;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Team $team;

    #[Validate('required|email|exists:users,email')]
    public string $newMemberEmail = '';

    public bool $showMigrationModal = false;

    public ?int $migrationTargetTeamId = null;

    public function mount(Team $team): void
    {
        $this->authorize('view', $team);
        $this->team = $team;
    }

    public function addMember(): void
    {
        $this->validate([
            'newMemberEmail' => [
                'required',
                'email',
                'exists:users,email',
            ],
        ]);

        $user = User::where('email', $this->newMemberEmail)->first();

        if ($this->team->users()->where('users.id', $user->id)->exists()) {
            $this->addError('newMemberEmail', 'This user is already a member of the team.');

            return;
        }

        $this->team->users()->attach($user->id);

        /** @var \App\Models\User $actingUser */
        $actingUser = auth()->user();

        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} added {$user->full_name} to team {$this->team->name}");

        $this->newMemberEmail = '';
        $this->dispatch('member-added');
    }

    public function removeMember(int $userId): void
    {
        if ($this->team->users()->count() <= 1) {
            $this->addError('member', 'Cannot remove the last member from a team.');

            return;
        }

        $member = User::find($userId);
        $this->team->users()->detach($userId);

        /** @var \App\Models\User $actingUser */
        $actingUser = auth()->user();
        $memberName = $member?->full_name ?? "user #{$userId}";

        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} removed {$memberName} from team {$this->team->name}");
        $this->dispatch('member-removed');
    }

    public function deleteTeam(): void
    {
        if ($this->team->isPersonalTeam()) {
            return;
        }

        if ($this->team->scheduledTasks()->count() > 0) {
            $this->showMigrationModal = true;

            return;
        }

        /** @var \App\Models\User $actingUser */
        $actingUser = auth()->user();
        $teamName = $this->team->name;

        $this->team->delete();
        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} deleted team {$teamName}");
        $this->redirect(route('teams.index'), navigate: true);
    }

    public function confirmMigration(): void
    {
        if ($this->migrationTargetTeamId === null) {
            $this->addError('migrationTargetTeamId', 'Please select a team to migrate tasks to.');

            return;
        }

        $targetTeam = Team::find($this->migrationTargetTeamId);

        if ($targetTeam === null) {
            $this->addError('migrationTargetTeamId', 'Selected team not found.');

            return;
        }

        $migratedCount = $this->team->scheduledTasks()->count();

        $this->team->scheduledTasks()->update([
            'team_id' => $targetTeam->id,
        ]);

        /** @var \App\Models\User $actingUser */
        $actingUser = auth()->user();

        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} migrated {$migratedCount} scheduled task(s) from team {$this->team->name} to team {$targetTeam->name}");

        $this->team->delete();
        SomethingNoteworthyHappened::dispatch("{$actingUser->full_name} deleted team {$this->team->name} after migrating tasks to team {$targetTeam->name}");
        $this->redirect(route('teams.index'), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $members = $this->team->users()->orderBy('surname')->orderBy('forenames')->get();
        $tasks = $this->team->scheduledTasks()->orderBy('name')->get();
        $availableTeams = Team::query()
            ->where('id', '!=', $this->team->id)
            ->orderBy('name')
            ->get();

        return view('livewire.teams.show', [
            'members' => $members,
            'tasks' => $tasks,
            'availableTeams' => $availableTeams,
        ]);
    }
}
