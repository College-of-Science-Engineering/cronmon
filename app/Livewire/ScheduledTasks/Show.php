<?php

namespace App\Livewire\ScheduledTasks;

use App\Events\SomethingNoteworthyHappened;
use App\Models\ScheduledTask;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    public ScheduledTask $task;

    public string $currentTab = 'details';

    public bool $silenceEnabled = false;

    #[Validate('in:1_hour,6_hours,24_hours,3_days,7_days,custom')]
    public string $silenceSelection = '1_hour';

    #[Validate('nullable|date|after:now')]
    public ?string $silenceCustomUntil = null;

    public function mount(ScheduledTask $task): void
    {
        $this->task = $task;
        $this->synchroniseSilenceState();
    }

    #[On('task-saved')]
    public function refreshTask(): void
    {
        $this->task->refresh();
        $this->synchroniseSilenceState();
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

    #[Layout('components.layouts.app')]
    public function render()
    {
        $this->task->load([
            'team',
            'creator',
            'taskRuns' => fn ($query) => $query->latest()->limit(20),
            'alerts' => fn ($query) => $query->latest()->limit(20),
        ]);

        $chartData = $this->prepareChartData();
        $runningTaskRun = $this->task->currentlyRunningTaskRun();
        $recentRuns = $this->task->taskRuns()
            ->orderBy('checked_in_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        return view('livewire.scheduled-tasks.show', [
            'chartData' => $chartData,
            'runningTaskRun' => $runningTaskRun,
            'recentRuns' => $recentRuns,
        ]);
    }

    protected function prepareChartData(): array
    {
        $runs = $this->task->taskRuns()
            ->orderBy('checked_in_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        if ($runs->isEmpty()) {
            return [];
        }

        $hasExecutionTimeData = $runs->contains(function ($run) {
            return $run->execution_time_seconds !== null && $run->execution_time_seconds > 0;
        });

        $chartData = $runs->map(function ($run, $index) {
            return [
                'run_number' => $index + 1,
                'date_time' => $run->checked_in_at->format('M j, Y g:i A'),
                'execution_time' => $run->execution_time_seconds ?? ($run->data['execution_time'] ?? 0),
            ];
        })->toArray();

        return [
            'hasExecutionTimeData' => $hasExecutionTimeData,
            'data' => $chartData,
        ];
    }

    protected function applySilence(?Carbon $until): void
    {
        $this->authorize('update', $this->task);

        $untilUtc = $until?->clone()->setTimezone('UTC');

        $this->task->alerts_silenced_until = $untilUtc;
        $this->task->save();

        $actingUser = auth()->user();
        $teamName = $this->task->team()->value('name');

        if ($actingUser !== null) {
            $message = $untilUtc === null
                ? "{$actingUser->full_name} cleared alert silence for scheduled task {$this->task->name} on team {$teamName}"
                : "{$actingUser->full_name} silenced alerts for scheduled task {$this->task->name} on team {$teamName} until {$untilUtc->toIso8601String()}";

            SomethingNoteworthyHappened::dispatch($message);
        }

        $this->task->refresh();
        $this->synchroniseSilenceState();
        $this->dispatch('task-saved');
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
        $this->task->load('team');

        $isTaskSilenced = $this->task->alerts_silenced_until !== null && $this->task->alerts_silenced_until->isFuture();

        $this->silenceEnabled = $isTaskSilenced;

        if ($isTaskSilenced) {
            $this->silenceSelection = 'custom';
            $this->silenceCustomUntil = $this->task->alerts_silenced_until
                ? $this->task->alerts_silenced_until
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
