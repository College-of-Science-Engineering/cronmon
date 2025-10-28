<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaskRun
 */
class TaskRunResource extends JsonResource
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
            'scheduled_task_id' => $this->scheduled_task_id,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'expected_at' => $this->expected_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'was_late' => $this->was_late,
            'lateness_minutes' => $this->lateness_minutes,
            'execution_time_seconds' => $this->execution_time_seconds,
            'execution_time_display' => $this->executionTime(),
            'data' => $this->data,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
