<?php

namespace App\Policies;

use App\Models\ScheduledTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScheduledTaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduledTask $scheduledTask): bool
    {
        return $this->isMemberOfTeam($user, $scheduledTask);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduledTask $scheduledTask): bool
    {
        return $this->isMemberOfTeam($user, $scheduledTask);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduledTask $scheduledTask): bool
    {
        return $this->isMemberOfTeam($user, $scheduledTask);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduledTask $scheduledTask): bool
    {
        return $this->isMemberOfTeam($user, $scheduledTask);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduledTask $scheduledTask): bool
    {
        return $this->isMemberOfTeam($user, $scheduledTask);
    }

    /**
     * Check if the user is a member of the team that owns the scheduled task.
     */
    protected function isMemberOfTeam(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->teams()->where('teams.id', $scheduledTask->team_id)->exists();
    }
}
