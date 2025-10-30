<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'surname',
        'forenames',
        'is_staff',
        'is_admin',
        'password',
        'personal_team_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $team = Team::create([
                'name' => $user->username,
                'slug' => $user->username,
                'user_id' => $user->id,
            ]);
            $user->teams()->attach($team);
            $user->personal_team_id = $team->id;
            $user->save();
        });
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function personalTeam(): HasOne
    {
        return $this->hasOne(Team::class, 'id', 'personal_team_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->forenames} {$this->surname}");
    }
}
