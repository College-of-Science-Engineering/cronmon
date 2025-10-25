<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledTask>
 */
class ScheduledTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduleType = fake()->randomElement(['simple', 'cron']);
        $scheduleValue = $scheduleType === 'simple'
            ? fake()->randomElement(['5m', '15m', '30m', '1h', '6h', '12h', 'daily'])
            : fake()->randomElement(['0 * * * *', '0 0 * * *', '0 3 * * *', '*/5 * * * *']);

        return [
            'team_id' => Team::factory(),
            'created_by' => User::factory(),
            'name' => fake()->randomElement([
                'Database Backup',
                'Send Daily Report',
                'Process Queue',
                'Clean Temp Files',
                'Sync Data',
                'Generate Reports',
            ]),
            'description' => fake()->optional()->sentence(),
            'schedule_type' => $scheduleType,
            'schedule_value' => $scheduleValue,
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo']),
            'grace_period_minutes' => fake()->randomElement([5, 10, 15, 30, 60]),
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => fake()->optional()->dateTimeBetween('-1 day', 'now'),
            'next_expected_at' => fake()->optional()->dateTimeBetween('now', '+1 day'),
            'status' => fake()->randomElement(['ok', 'pending', 'alerting', 'paused']),
        ];
    }
}
