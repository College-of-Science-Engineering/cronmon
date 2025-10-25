<?php

namespace App\Livewire\Forms;

use App\Models\ScheduledTask;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ScheduledTaskForm extends Form
{
    public ?ScheduledTask $scheduledTask = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    #[Validate('required|in:simple,cron')]
    public string $schedule_type = 'simple';

    #[Validate('required|string')]
    public string $schedule_value = '';

    #[Validate('required|string|timezone:all')]
    public string $timezone = 'UTC';

    #[Validate('required|integer|min:1|max:1440')]
    public int $grace_period_minutes = 10;

    public function setScheduledTask(ScheduledTask $task): void
    {
        $this->scheduledTask = $task;

        $this->name = $task->name;
        $this->description = $task->description;
        $this->schedule_type = $task->schedule_type;
        $this->schedule_value = $task->schedule_value;
        $this->timezone = $task->timezone;
        $this->grace_period_minutes = $task->grace_period_minutes;
    }

    public function save(?int $team_id = null): ScheduledTask
    {
        $this->validate();

        if ($this->scheduledTask) {
            $this->scheduledTask->update(array_merge(
                $this->only([
                    'name',
                    'description',
                    'schedule_type',
                    'schedule_value',
                    'timezone',
                    'grace_period_minutes',
                ]),
                $team_id ? ['team_id' => $team_id] : []
            ));

            return $this->scheduledTask;
        }

        return ScheduledTask::create([
            'team_id' => $team_id ?? auth()->user()->personalTeam()->id,
            'created_by' => auth()->id(),
            'name' => $this->name,
            'description' => $this->description,
            'schedule_type' => $this->schedule_type,
            'schedule_value' => $this->schedule_value,
            'timezone' => $this->timezone,
            'grace_period_minutes' => $this->grace_period_minutes,
            'unique_check_in_token' => Str::uuid()->toString(),
            'status' => 'pending',
        ]);
    }
}
