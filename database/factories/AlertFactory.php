<?php

namespace Database\Factories;

use App\Models\ScheduledTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alertType = fake()->randomElement(['missed', 'late', 'recovered']);
        $isAcknowledged = fake()->boolean(60);

        $messages = [
            'missed' => 'Task did not check in as expected',
            'late' => 'Task checked in late',
            'recovered' => 'Task has recovered and is checking in normally',
        ];

        return [
            'scheduled_task_id' => ScheduledTask::factory(),
            'alert_type' => $alertType,
            'triggered_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'acknowledged_at' => $isAcknowledged ? fake()->dateTimeBetween('-3 days', 'now') : null,
            'acknowledged_by' => $isAcknowledged ? User::factory() : null,
            'message' => $messages[$alertType],
        ];
    }
}
