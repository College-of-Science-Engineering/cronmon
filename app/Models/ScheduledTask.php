<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('team', function (Builder $teamQuery) use ($user): void {
            $teamQuery->whereHas('users', function (Builder $usersQuery) use ($user): void {
                $usersQuery->whereKey($user->getKey());
            });
        });
    }

    public function scopeCheckedBetween(Builder $query, array|string|null $range): Builder
    {
        if ($range === null) {
            return $query;
        }

        if (is_string($range)) {
            $range = array_filter(explode(',', $range));
        }

        [$from, $to] = array_pad($range, 2, null);

        return $query->whereHas('taskRuns', function (Builder $taskRunQuery) use ($from, $to): void {
            $fromDate = self::normaliseFilterDate($from);
            $toDate = self::normaliseFilterDate($to);

            if ($fromDate !== null) {
                $taskRunQuery->where('checked_in_at', '>=', $fromDate);
            }

            if ($toDate !== null) {
                $taskRunQuery->where('checked_in_at', '<=', $toDate);
            }
        });
    }

    public function scopeSilenced(Builder $query, bool $silenced): Builder
    {
        $now = now();

        if ($silenced) {
            return $query->where(function (Builder $builder) use ($now): void {
                $builder->where(function (Builder $inner) use ($now): void {
                    $inner->whereNotNull('alerts_silenced_until')
                        ->where('alerts_silenced_until', '>', $now);
                })->orWhereHas('team', function (Builder $teamQuery) use ($now): void {
                    $teamQuery->whereNotNull('alerts_silenced_until')
                        ->where('alerts_silenced_until', '>', $now);
                });
            });
        }

        return $query
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('alerts_silenced_until')
                    ->orWhere('alerts_silenced_until', '<=', $now);
            })
            ->whereDoesntHave('team', function (Builder $teamQuery) use ($now): void {
                $teamQuery->whereNotNull('alerts_silenced_until')
                    ->where('alerts_silenced_until', '>', $now);
            });
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

    public function getSilencedUntil(): ?Carbon
    {
        if ($this->alerts_silenced_until !== null && $this->alerts_silenced_until->isFuture()) {
            return $this->alerts_silenced_until;
        }

        if ($this->team->alerts_silenced_until !== null && $this->team->alerts_silenced_until->isFuture()) {
            return $this->team->alerts_silenced_until;
        }

        return null;
    }

    public function currentlyRunningTaskRun(): ?TaskRun
    {
        return $this->taskRuns()
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();
    }

    protected static function normaliseFilterDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::make($value)?->utc();
    }
}
