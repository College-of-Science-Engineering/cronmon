<?php

namespace Database\Factories;

use App\Models\ScheduledTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskRun>
 */
class TaskRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expectedAt = fake()->dateTimeBetween('-1 day', 'now');
        $wasLate = fake()->boolean(30);
        $latenessMinutes = $wasLate ? fake()->numberBetween(1, 120) : null;
        $checkedInAt = $wasLate
            ? (clone $expectedAt)->modify("+{$latenessMinutes} minutes")
            : fake()->dateTimeBetween($expectedAt, '+5 minutes');

        return [
            'scheduled_task_id' => ScheduledTask::factory(),
            'checked_in_at' => $checkedInAt,
            'expected_at' => $expectedAt,
            'was_late' => $wasLate,
            'lateness_minutes' => $latenessMinutes,
        ];
    }
}
