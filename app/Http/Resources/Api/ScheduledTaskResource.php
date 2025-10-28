<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ScheduledTask
 */
class ScheduledTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'team_id' => $this->team_id,
            'name' => $this->name,
            'description' => $this->description,
            'schedule_type' => $this->schedule_type,
            'schedule_value' => $this->schedule_value,
            'timezone' => $this->timezone,
            'grace_period_minutes' => $this->grace_period_minutes,
            'status' => $this->status,
            'last_checked_in_at' => $this->last_checked_in_at?->toIso8601String(),
            'next_expected_at' => $this->next_expected_at?->toIso8601String(),
            'alerts_silenced_until' => $this->alerts_silenced_until?->toIso8601String(),
            'silence' => [
                'active' => $this->isSilenced(),
                'cause' => $this->getSilencedCause(),
                'until' => $this->getSilencedUntil()?->toIso8601String(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'team' => TeamResource::make($this->whenLoaded('team')),
            'task_runs' => TaskRunResource::collection($this->whenLoaded('taskRuns')),
            'links' => [
                'ping' => $this->getPingUrl(),
            ],
        ];
    }
}
