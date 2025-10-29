<?php

namespace App\Livewire\Teams;

use App\Events\SomethingNoteworthyHappened;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public const SILENCE_OPTIONS = [
        '1_hour' => '1 hour',
        '6_hours' => '6 hours',
        '24_hours' => '24 hours',
        '3_days' => '3 days',
        '7_days' => '7 days',
        'custom' => 'Custom',
    ];

    public Team $team;

    #[Validate('required|email|exists:users,email')]
    public string $newMemberEmail = '';

    public bool $showMigrationModal = false;

    public ?int $migrationTargetTeamId = null;

    public bool $silenceEnabled = false;

    #[Validate('in:1_hour,6_hours,24_hours,3_days,7_days,custom')]
    public string $silenceSelection = '1_hour';

    #[Validate('nullable|date|after:now')]
    public ?string $silenceCustomUntil = null;

    public function mount(Team $team): void
    {
        $this->authorize('view', $team);
        $this->team = $team;
        $this->synchroniseSilenceState();
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

    public function updatedSilenceEnabled(bool $value): void
    {
        if (! $value) {
            $this->applySilence(null);

            return;
        }

        $this->silenceSelection = '1_hour';
        $this->silenceCustomUntil = null;

        $this->applySilence($this->resolvePredefinedSilence($this->silenceSelection));
    }

    public function updatedSilenceSelection(string $value): void
    {
        if (! $this->silenceEnabled) {
            return;
        }

        if ($value === 'custom') {
            $this->silenceCustomUntil = now()
                ->addHour()
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d\TH:i');

            return;
        }

        $this->silenceCustomUntil = null;

        $this->applySilence($this->resolvePredefinedSilence($value));
    }

    public function updatedSilenceCustomUntil(?string $value): void
    {
        if (! $this->silenceEnabled || $this->silenceSelection !== 'custom') {
            return;
        }

        $this->validateOnly('silenceCustomUntil');

        if ($value === null) {
            return;
        }

        $until = $this->parseCustomSilence();

        if ($until === null) {
            return;
        }

        $this->applySilence($until);
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

    protected function applySilence(?Carbon $until): void
    {
        $this->authorize('update', $this->team);

        $untilUtc = $until?->clone()->setTimezone('UTC');

        $this->team->alerts_silenced_until = $untilUtc;
        $this->team->save();

        $actingUser = auth()->user();

        if ($actingUser !== null) {
            $message = $untilUtc === null
                ? "{$actingUser->full_name} cleared alert silence for team {$this->team->name}"
                : "{$actingUser->full_name} silenced alerts for team {$this->team->name} until {$untilUtc->toIso8601String()}";

            SomethingNoteworthyHappened::dispatch($message);
        }

        $this->team->refresh();
        $this->synchroniseSilenceState();
    }

    protected function resolvePredefinedSilence(?string $selection): ?Carbon
    {
        $now = now()->setTimezone('UTC');

        return match ($selection) {
            '1_hour' => $now->clone()->addHour(),
            '6_hours' => $now->clone()->addHours(6),
            '24_hours' => $now->clone()->addDay(),
            '3_days' => $now->clone()->addDays(3),
            '7_days' => $now->clone()->addDays(7),
            default => null,
        };
    }

    protected function parseCustomSilence(): ?Carbon
    {
        if ($this->silenceCustomUntil === null) {
            return null;
        }

        try {
            return Carbon::parse($this->silenceCustomUntil, config('app.timezone'))
                ->setTimezone('UTC');
        } catch (\Throwable) {
            $this->addError('silenceCustomUntil', 'Please choose a valid future date and time.');

            return null;
        }
    }

    protected function synchroniseSilenceState(): void
    {
        $isSilenced = $this->team->alerts_silenced_until !== null && $this->team->alerts_silenced_until->isFuture();

        $this->silenceEnabled = $isSilenced;

        if ($isSilenced) {
            $this->silenceSelection = 'custom';
            $this->silenceCustomUntil = $this->team->alerts_silenced_until
                ? $this->team->alerts_silenced_until
                    ->clone()
                    ->setTimezone(config('app.timezone'))
                    ->format('Y-m-d\TH:i')
                : null;
        } else {
            $this->silenceSelection = '1_hour';
            $this->silenceCustomUntil = null;
        }
    }
}
