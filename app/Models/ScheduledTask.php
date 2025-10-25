<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledTask extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduledTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'created_by',
        'name',
        'description',
        'schedule_type',
        'schedule_value',
        'timezone',
        'grace_period_minutes',
        'unique_check_in_token',
        'last_checked_in_at',
        'next_expected_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_in_at' => 'datetime',
            'next_expected_at' => 'datetime',
            'alerts_silenced_until' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskRuns(): HasMany
    {
        return $this->hasMany(TaskRun::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function getPingUrl(): string
    {
        return route('api.ping', $this->unique_check_in_token);
    }

    public function isSilenced(): bool
    {
        if ($this->alerts_silenced_until !== null && $this->alerts_silenced_until->isFuture()) {
            return true;
        }

        if ($this->team->alerts_silenced_until !== null && $this->team->alerts_silenced_until->isFuture()) {
            return true;
        }

        return false;
    }

    public function getSilencedCause(): ?string
    {
        if ($this->alerts_silenced_until !== null && $this->alerts_silenced_until->isFuture()) {
            return 'task';
        }

        if ($this->team->alerts_silenced_until !== null && $this->team->alerts_silenced_until->isFuture()) {
            return 'team';
        }

        return null;
    }

    public function getSilencedUntil(): ?\Illuminate\Support\Carbon
    {
        if ($this->alerts_silenced_until !== null && $this->alerts_silenced_until->isFuture()) {
            return $this->alerts_silenced_until;
        }

        if ($this->team->alerts_silenced_until !== null && $this->team->alerts_silenced_until->isFuture()) {
            return $this->team->alerts_silenced_until;
        }

        return null;
    }
}
