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
        'expected_at',
        'was_late',
        'lateness_minutes',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'expected_at' => 'datetime',
            'was_late' => 'boolean',
            'data' => 'array',
        ];
    }

    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class);
    }
}
