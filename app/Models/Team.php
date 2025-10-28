<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'alerts_silenced_until' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'personal_team_id', 'id');
    }

    public function scheduledTasks(): HasMany
    {
        return $this->hasMany(ScheduledTask::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('users', function (Builder $usersQuery) use ($user): void {
            $usersQuery->whereKey($user->getKey());
        });
    }

    public function isPersonalTeam(): bool
    {
        return User::where('personal_team_id', $this->id)->exists();
    }

    public function isPersonalTeamForUser(User $user): bool
    {
        return $this->id === $user->personal_team_id;
    }

    public function isSilenced(): bool
    {
        return $this->alerts_silenced_until !== null && $this->alerts_silenced_until->isFuture();
    }
}
