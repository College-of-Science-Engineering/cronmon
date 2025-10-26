<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin2x',
            'forenames' => 'Admin',
            'surname' => 'Person',
            'password' => bcrypt('secret'),
            'email' => 'admin@example.com',
        ]);
        $user1 = User::factory()->create([
            'username' => 'abc1x',
            'forenames' => 'Billy',
            'surname' => 'Smith',
            'email' => 'billy@example.com',
        ]);

        $user2 = User::factory()->create([
            'username' => 'def2y',
            'forenames' => 'Jane',
            'surname' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $adminTeam = Team::create([
            'name' => 'admin2x',
            'slug' => 'admin2x',
        ]);
        $admin->teams()->attach($adminTeam);
        $admin->personal_team_id = $adminTeam->id;
        $admin->save();

        $personalTeam1 = Team::create([
            'name' => 'abc1x',
            'slug' => 'abc1x',
        ]);
        $personalTeam1->users()->attach($user1);
        $user1->personal_team_id = $personalTeam1->id;
        $user1->save();

        $personalTeam2 = Team::create([
            'name' => 'def2y',
            'slug' => 'def2y',
        ]);
        $user2->teams()->attach($personalTeam2);
        $user2->personal_team_id = $personalTeam2->id;
        $user2->save();

        $sharedTeam = Team::create([
            'name' => 'DevOps Team',
            'slug' => 'devops-team',
        ]);
        $sharedTeam->users()->attach([$user1->id, $user2->id]);

        // create two admin tasks
        ScheduledTask::create([
            'team_id' => $adminTeam->id,
            'created_by' => $admin->id,
            'name' => 'Admin Task 1',
            'description' => 'Admin task 1',
            'schedule_type' => 'simple',
            'schedule_value' => '5m',
            'timezone' => 'UTC',
            'grace_period_minutes' => 30,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => now()->subMinutes(rand(1, 5)),
            'status' => 'ok',
        ]);
        ScheduledTask::create([
            'team_id' => $adminTeam->id,
            'created_by' => $admin->id,
            'name' => 'Admin Task 2',
            'description' => 'Admin task 2',
            'schedule_type' => 'simple',
            'schedule_value' => '1h',
            'timezone' => 'UTC',
            'grace_period_minutes' => 30,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => now()->subMinutes(rand(1, 50)),
            'status' => 'ok',
        ]);

        // Task 1: Healthy task with good check-in history (for chart visualization)
        $task1 = ScheduledTask::create([
            'team_id' => $personalTeam1->id,
            'created_by' => $user1->id,
            'name' => 'Database Backup',
            'description' => 'Nightly backup of production database',
            'schedule_type' => 'cron',
            'schedule_value' => '0 3 * * *',
            'timezone' => 'UTC',
            'grace_period_minutes' => 30,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => now()->subHours(3),
            'status' => 'ok',
        ]);

        // Create 30 task runs over the last 30 days with execution time data
        for ($i = 29; $i >= 0; $i--) {
            TaskRun::create([
                'scheduled_task_id' => $task1->id,
                'checked_in_at' => now()->subDays($i)->setTime(3, rand(0, 5), rand(0, 59)),
                'expected_at' => now()->subDays($i)->setTime(3, 0, 0),
                'was_late' => $i % 10 === 0, // Every 10th run was late
                'lateness_minutes' => $i % 10 === 0 ? rand(5, 20) : null,
                'data' => [
                    'execution_time' => rand(45, 180), // seconds
                    'records_processed' => rand(1000, 5000),
                    'status' => 'success',
                ],
            ]);
        }

        // Task 2: Currently alerting with mixed history
        $task2 = ScheduledTask::create([
            'team_id' => $sharedTeam->id,
            'created_by' => $user2->id,
            'name' => 'Process Queue',
            'description' => 'Process pending jobs from the queue',
            'schedule_type' => 'simple',
            'schedule_value' => '5m',
            'timezone' => 'UTC',
            'grace_period_minutes' => 10,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => now()->subHours(2),
            'status' => 'alerting',
        ]);

        // Create 20 task runs with some late ones
        for ($i = 19; $i >= 0; $i--) {
            $wasLate = $i < 3; // Last 3 runs were late
            TaskRun::create([
                'scheduled_task_id' => $task2->id,
                'checked_in_at' => now()->subMinutes($i * 5 + rand(0, 2)),
                'expected_at' => now()->subMinutes($i * 5),
                'was_late' => $wasLate,
                'lateness_minutes' => $wasLate ? rand(15, 45) : null,
                'data' => [
                    'execution_time' => rand(2, 8), // seconds
                    'jobs_processed' => rand(10, 50),
                ],
            ]);
        }

        // Create alerts for task 2
        Alert::create([
            'scheduled_task_id' => $task2->id,
            'alert_type' => 'missed',
            'triggered_at' => now()->subHours(2),
            'message' => "Task 'Process Queue' has missed its scheduled run and exceeded the grace period.",
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ]);

        Alert::create([
            'scheduled_task_id' => $task2->id,
            'alert_type' => 'late',
            'triggered_at' => now()->subHours(1),
            'message' => "Task 'Process Queue' is running late.",
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ]);

        Alert::create([
            'scheduled_task_id' => $task2->id,
            'alert_type' => 'recovered',
            'triggered_at' => now()->subDays(1),
            'message' => "Task 'Process Queue' has recovered and is checking in again.",
            'acknowledged_by' => $user1->id,
            'acknowledged_at' => now()->subDays(1)->addHours(1),
        ]);

        // Task 3: Pending task (never checked in)
        $task3 = ScheduledTask::create([
            'team_id' => $personalTeam1->id,
            'created_by' => $user1->id,
            'name' => 'Log Rotation',
            'description' => 'Weekly log file rotation and cleanup',
            'schedule_type' => 'cron',
            'schedule_value' => '0 2 * * 0',
            'timezone' => 'UTC',
            'grace_period_minutes' => 60,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => null,
            'status' => 'pending',
        ]);

        // Task 4: Paused task with history
        $task4 = ScheduledTask::create([
            'team_id' => $sharedTeam->id,
            'created_by' => $user2->id,
            'name' => 'Cache Warmup',
            'description' => 'Warm up application cache',
            'schedule_type' => 'simple',
            'schedule_value' => '1h',
            'timezone' => 'UTC',
            'grace_period_minutes' => 15,
            'unique_check_in_token' => Str::uuid()->toString(),
            'last_checked_in_at' => now()->subDays(7),
            'status' => 'paused',
        ]);

        // Create 10 old task runs for paused task
        for ($i = 9; $i >= 0; $i--) {
            TaskRun::create([
                'scheduled_task_id' => $task4->id,
                'checked_in_at' => now()->subDays(7 + $i)->setTime(rand(8, 17), 0, 0),
                'expected_at' => now()->subDays(7 + $i)->setTime(rand(8, 17), 0, 0),
                'was_late' => false,
                'lateness_minutes' => null,
                'data' => [
                    'execution_time' => rand(10, 30),
                    'cache_keys_warmed' => rand(100, 500),
                ],
            ]);
        }
    }
}
