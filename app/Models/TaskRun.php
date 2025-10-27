<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRun extends Model
{
    /** @use HasFactory<\Database\Factories\TaskRunFactory> */
    use HasFactory;

    protected $fillable = [
        'scheduled_task_id',
        'checked_in_at',
        'started_at',
        'finished_at',
        'execution_time_seconds',
        'expected_at',
        'was_late',
        'lateness_minutes',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expected_at' => 'datetime',
            'was_late' => 'boolean',
            'data' => 'array',
        ];
    }

    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class);
    }

    public function isRunning(): bool
    {
        return $this->started_at !== null && $this->finished_at === null;
    }

    public function isComplete(): bool
    {
        return $this->finished_at !== null;
    }

    public function executionTime(): ?string
    {
        if (! $this->execution_time_seconds) {
            return null;
        }

        $seconds = $this->execution_time_seconds;

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? "{$minutes}m {$remainingSeconds}s"
                : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours}h";
    }
}
