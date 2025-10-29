<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use App\Models\Team;
use App\Models\User;
use App\Services\ScheduleCalculator;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    protected \Faker\Generator $faker;

    protected ScheduleCalculator $calculator;

    protected Carbon $now;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = fake('en_GB');
        $this->calculator = app(ScheduleCalculator::class);
        $this->now = now();

        $users = $this->seedUsers();
        [$centralTeams, $schoolTeams] = $this->seedTeams($users);

        $statusDistribution = [
            'ok' => 350,
            'alerting' => 60,
            'pending' => 50,
            'paused' => 40,
        ];

        $statusStack = $this->buildStatusStack($statusDistribution);

        $taskNamePool = [
            'Database Backup',
            'Student Records Sync',
            'Exam Timetable Export',
            'Course Catalogue Refresh',
            'Process Application Queue',
            'ETL: Warehouse Load',
            'Daily Attendance Digest',
            'Virtual Learning Snapshot',
            'Payroll Processing',
            'Timetable Reconciliation',
            'Research Data Sync',
            'Email Digest Generator',
            'Cache Warmup Cycle',
            'Log Rotation',
            'Infrastructure Health Check',
        ];

        $taskDescriptionPool = [
            'Automates the nightly maintenance step to keep systems aligned.',
            'Ensures downstream integrations receive fresh information.',
            'Critical process monitored closely during term time peaks.',
            'Feeds analytics dashboards relied upon by department heads.',
            'Supports student experience and operational reporting.',
        ];

        $createdTasks = [
            'ok' => collect(),
            'alerting' => collect(),
            'pending' => collect(),
            'paused' => collect(),
        ];

        $totalTasks = 500;
        $centralTaskTarget = (int) round($totalTasks * 0.6);
        $schoolTaskTarget = $totalTasks - $centralTaskTarget;

        $this->seedTasksForTeams(
            $centralTeams,
            $centralTaskTarget,
            $statusStack,
            $taskNamePool,
            $taskDescriptionPool,
            'central',
            $createdTasks
        );

        $this->seedTasksForTeams(
            $schoolTeams,
            $schoolTaskTarget,
            $statusStack,
            $taskNamePool,
            $taskDescriptionPool,
            'school',
            $createdTasks
        );

        $this->applySilencing($schoolTeams, $createdTasks);
        $this->seedAuditLogs($users, $centralTeams, $schoolTeams);
    }

    protected function seedUsers(): Collection
    {
        $people = collect([
            ['forenames' => 'Admin', 'surname' => 'McAdmin', 'username' => 'admin2x', 'is_admin' => true, 'password' => 'secret'],
            ['forenames' => 'Alice', 'surname' => 'Taylor', 'username' => 'alice.taylor', 'is_admin' => true],
            ['forenames' => 'Benjamin', 'surname' => 'Mitchell', 'username' => 'ben.mitchell', 'is_admin' => true],
            ['forenames' => 'Carol', 'surname' => 'Chen', 'username' => 'carol.chen', 'is_admin' => true],
            ['forenames' => 'Daniel', 'surname' => 'Hughes', 'username' => 'daniel.hughes', 'is_admin' => true],
            ['forenames' => 'Emma', 'surname' => 'Patel', 'username' => 'emma.patel'],
            ['forenames' => 'Franklin', 'surname' => 'Greene', 'username' => 'franklin.greene'],
            ['forenames' => 'Georgia', 'surname' => 'Clarke', 'username' => 'georgia.clarke'],
            ['forenames' => 'Henry', 'surname' => 'Sanders', 'username' => 'henry.sanders'],
            ['forenames' => 'Isabel', 'surname' => 'Quinn', 'username' => 'isabel.quinn'],
            ['forenames' => 'Jackson', 'surname' => 'Holt', 'username' => 'jackson.holt'],
            ['forenames' => 'Karen', 'surname' => 'Walters', 'username' => 'karen.walters'],
            ['forenames' => 'Leo', 'surname' => 'Foster', 'username' => 'leo.foster'],
            ['forenames' => 'Mia', 'surname' => 'Robins', 'username' => 'mia.robins'],
            ['forenames' => 'Noah', 'surname' => 'Jefferson', 'username' => 'noah.jefferson'],
            ['forenames' => 'Olivia', 'surname' => 'Miles', 'username' => 'olivia.miles'],
            ['forenames' => 'Patrick', 'surname' => 'Lawson', 'username' => 'patrick.lawson'],
            ['forenames' => 'Quinn', 'surname' => 'Dawson', 'username' => 'quinn.dawson'],
            ['forenames' => 'Rachel', 'surname' => 'Nguyen', 'username' => 'rachel.nguyen'],
            ['forenames' => 'Samuel', 'surname' => 'Owens', 'username' => 'samuel.owens'],
            ['forenames' => 'Tessa', 'surname' => 'Barnes', 'username' => 'tessa.barnes'],
            ['forenames' => 'Uma', 'surname' => 'Gibson', 'username' => 'uma.gibson'],
            ['forenames' => 'Victor', 'surname' => 'Lopez', 'username' => 'victor.lopez'],
            ['forenames' => 'Willa', 'surname' => 'Hayes', 'username' => 'willa.hayes'],
            ['forenames' => 'Xavier', 'surname' => 'Long', 'username' => 'xavier.long'],
            ['forenames' => 'Yasmin', 'surname' => 'Edge', 'username' => 'yasmin.edge'],
            ['forenames' => 'Zachary', 'surname' => 'Howe', 'username' => 'zachary.howe'],
            ['forenames' => 'Amelia', 'surname' => 'Rivers', 'username' => 'amelia.rivers'],
            ['forenames' => 'Brian', 'surname' => 'Coates', 'username' => 'brian.coates'],
            ['forenames' => 'Chloe', 'surname' => 'Danvers', 'username' => 'chloe.danvers'],
            ['forenames' => 'Derek', 'surname' => 'Hart', 'username' => 'derek.hart'],
        ]);

        return $people->map(function (array $person) {
            $person['password'] = $person['password'] ?? Str::random(10);
            $user = User::factory()->create([
                'username' => $person['username'],
                'forenames' => $person['forenames'],
                'surname' => $person['surname'],
                'email' => Str::of($person['username'])->replace('.', '_')->append('@example.edu'),
                'is_admin' => $person['is_admin'] ?? false,
                'password' => bcrypt($person['password']),
            ]);

            $personalTeam = Team::create([
                'name' => $user->username,
                'slug' => Str::slug($user->username),
            ]);

            $user->teams()->attach($personalTeam->id);
            $user->forceFill(['personal_team_id' => $personalTeam->id])->save();

            return $user;
        });
    }

    /** @param Collection<int, User> $users */
    protected function seedTeams(Collection $users): array
    {
        $centralTeamNames = [
            'Central IT Operations',
            'DevOps Platform',
            'Data Services',
            'Student Services',
        ];

        $schoolTeamNames = [
            'School of Engineering',
            'School of Chemistry',
            'School of PHAS',
            'School of Computer Science',
            'School of Mathematics',
            'School of Geography & Earth Sciences',
        ];

        $centralTeams = collect($centralTeamNames)->map(fn (string $name) => Team::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        $schoolTeams = collect($schoolTeamNames)->map(fn (string $name) => Team::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        $centralCount = $centralTeams->count();
        foreach ($users as $index => $user) {
            $centralTeam = $centralTeams[$index % $centralCount];
            $user->teams()->syncWithoutDetaching([$centralTeam->id]);
        }

        $schoolChunks = $users->values()->chunk(5);
        foreach ($schoolChunks as $index => $chunk) {
            $schoolTeam = $schoolTeams[$index] ?? $schoolTeams->random();
            foreach ($chunk as $user) {
                $user->teams()->syncWithoutDetaching([$schoolTeam->id]);
            }
        }

        $users->each(function (User $user) use ($centralTeams) {
            if ($this->faker->boolean(30)) {
                $additionalTeam = $centralTeams->random();
                $user->teams()->syncWithoutDetaching([$additionalTeam->id]);
            }
        });

        $centralTeams = $centralTeams->map(function (Team $team) {
            return $team->load('users');
        });

        $schoolTeams = $schoolTeams->map(function (Team $team) {
            return $team->load('users');
        });

        return [$centralTeams->values(), $schoolTeams->values()];
    }

    protected function buildStatusStack(array $distribution): array
    {
        $stack = [];

        foreach ($distribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $stack[] = $status;
            }
        }

        shuffle($stack);

        return $stack;
    }

    protected function seedTasksForTeams(
        Collection $teams,
        int $targetCount,
        array &$statusStack,
        array $taskNamePool,
        array $taskDescriptionPool,
        string $context,
        array &$createdTasks
    ): void {
        if ($teams->isEmpty() || $targetCount <= 0) {
            return;
        }

        $allocation = $this->distributeCounts($teams, $targetCount);

        foreach ($teams as $team) {
            $this->createTasksForTeam(
                $team,
                $allocation[$team->id] ?? 0,
                $statusStack,
                $taskNamePool,
                $taskDescriptionPool,
                $context,
                $createdTasks
            );
        }
    }

    protected function distributeCounts(Collection $teams, int $total): array
    {
        $result = [];
        $count = $teams->count();

        if ($count === 0) {
            return $result;
        }

        $base = intdiv($total, $count);
        $remainder = $total % $count;

        foreach ($teams->values() as $index => $team) {
            $result[$team->id] = $base + ($index < $remainder ? 1 : 0);
        }

        return $result;
    }

    protected function createTasksForTeam(
        Team $team,
        int $count,
        array &$statusStack,
        array $taskNamePool,
        array $taskDescriptionPool,
        string $context,
        array &$createdTasks
    ): void {
        if ($count <= 0 || $team->users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $status = $this->pullStatus($statusStack);
            $schedule = $this->chooseSchedule($context);
            $gracePeriod = $this->gracePeriodMinutes($schedule['type'], $schedule['value']);

            [$lastCheckIn, $nextExpected] = $this->determineTiming($schedule, $status, $gracePeriod);

            $creator = $team->users->random();
            $task = ScheduledTask::create([
                'team_id' => $team->id,
                'created_by' => $creator->id,
                'name' => Arr::random($taskNamePool),
                'description' => Arr::random($taskDescriptionPool),
                'schedule_type' => $schedule['type'],
                'schedule_value' => $schedule['value'],
                'timezone' => $this->timezoneForContext($context),
                'grace_period_minutes' => $gracePeriod,
                'unique_check_in_token' => Str::uuid()->toString(),
                'last_checked_in_at' => $lastCheckIn,
                'next_expected_at' => $nextExpected,
                'status' => $status,
            ]);

            $historyCount = $schedule['type'] === 'simple'
                ? $this->historyCountForSimple($schedule['value'])
                : $this->historyCountForCronInterval($schedule['interval_minutes']);

            $this->seedTaskHistory(
                $task,
                $status,
                $schedule,
                $historyCount,
                $lastCheckIn
            );

            $this->seedAlerts($task, $status, $team->users);

            $createdTasks[$status]->push($task);
        }
    }

    protected function pullStatus(array &$statusStack): string
    {
        if (empty($statusStack)) {
            return 'ok';
        }

        return array_pop($statusStack);
    }

    protected function chooseSchedule(string $context): array
    {
        $simpleWeights = [
            '5m' => 4,
            '15m' => 12,
            '30m' => 10,
            '1h' => 46,
            '6h' => 10,
            '12h' => 6,
            'daily' => 44,
        ];

        $cronOptions = [
            ['value' => '0 3 * * *', 'interval_minutes' => 1440],
            ['value' => '15 1 * * 1-5', 'interval_minutes' => 1440],
            ['value' => '0 0 * * 0', 'interval_minutes' => 10080],
            ['value' => '0 6 1 * *', 'interval_minutes' => 43200],
        ];

        $cronChance = $context === 'central' ? 18 : 10;

        if ($this->faker->boolean($cronChance)) {
            $option = Arr::random($cronOptions);

            return [
                'type' => 'cron',
                'value' => $option['value'],
                'interval_minutes' => $option['interval_minutes'],
            ];
        }

        $value = $this->weightedRandom($simpleWeights);

        return [
            'type' => 'simple',
            'value' => $value,
            'interval_minutes' => $this->simpleIntervalMinutes($value),
        ];
    }

    protected function weightedRandom(array $weights): string
    {
        $expanded = [];

        foreach ($weights as $value => $weight) {
            $expanded = array_merge($expanded, array_fill(0, $weight, $value));
        }

        return Arr::random($expanded);
    }

    protected function simpleIntervalMinutes(string $value): int
    {
        return match ($value) {
            '5m' => 5,
            '15m' => 15,
            '30m' => 30,
            '1h' => 60,
            '6h' => 360,
            '12h' => 720,
            'daily' => 1440,
            default => 60,
        };
    }

    protected function gracePeriodMinutes(string $type, string $value): int
    {
        if ($type === 'cron') {
            return match ($value) {
                '0 0 * * 0' => 180,
                '0 6 1 * *' => 720,
                default => 60,
            };
        }

        return match ($value) {
            '5m' => 3,
            '15m' => 5,
            '30m' => 10,
            '1h' => 15,
            '6h' => 30,
            '12h' => 45,
            'daily' => 60,
            default => 15,
        };
    }

    protected function determineTiming(array $schedule, string $status, int $gracePeriod): array
    {
        if ($status === 'pending') {
            return [null, null];
        }

        $interval = $schedule['interval_minutes'];

        if ($status === 'paused') {
            $last = $this->now->copy()->subDays($this->faker->numberBetween(7, 30))->setTime(
                $this->faker->numberBetween(0, 23),
                $this->faker->numberBetween(0, 59)
            );

            return [$last, null];
        }

        if ($status === 'alerting') {
            $last = $this->now->copy()->subMinutes($interval + $gracePeriod + $this->faker->numberBetween(20, 160));
        } else {
            $upperBound = max($interval, (int) floor($interval * 0.6));
            $last = $this->now->copy()->subMinutes($this->faker->numberBetween((int) floor($interval * 0.1), $upperBound));
        }

        $next = $this->calculator->calculateNextExpectedTime(
            $schedule['type'],
            $schedule['value'],
            $last->copy()
        );

        return [$last, $next];
    }

    protected function historyCountForSimple(string $value): int
    {
        return match ($value) {
            '5m' => 60,
            '15m' => 64,
            '30m' => 48,
            '1h' => 72,
            '6h' => 40,
            '12h' => 30,
            'daily' => 30,
            default => 40,
        };
    }

    protected function historyCountForCronInterval(int $intervalMinutes): int
    {
        if ($intervalMinutes >= 40000) {
            return 12;
        }

        if ($intervalMinutes >= 8000) {
            return 16;
        }

        return 30;
    }

    protected function seedTaskHistory(
        ScheduledTask $task,
        string $status,
        array $schedule,
        int $historyCount,
        ?Carbon $lastCheckIn
    ): void {
        if ($lastCheckIn === null || $historyCount <= 0) {
            return;
        }

        $interval = $schedule['interval_minutes'];

        // Decide if this task uses start/finish tracking (70% chance)
        $usesStartFinish = $this->faker->boolean(70);

        for ($i = 0; $i < $historyCount; $i++) {
            $checkedAt = $lastCheckIn->copy()->subMinutes($interval * $i);

            if ($checkedAt->lessThan($this->now->copy()->subDays(120))) {
                break;
            }

            $isLate = false;
            $lateness = null;

            if ($status === 'alerting' && $i === 0) {
                $isLate = true;
                $lateness = $this->faker->numberBetween(
                    max(10, (int) floor($interval * 0.2)),
                    max(20, (int) floor($interval * 0.6))
                );
                $expectedAt = $checkedAt->copy()->subMinutes($lateness);
            } elseif ($status === 'ok' && $this->faker->boolean(12)) {
                $isLate = true;
                $lateness = $this->faker->numberBetween(2, max(5, (int) floor($interval * 0.25)));
                $expectedAt = $checkedAt->copy()->subMinutes($lateness);
            } else {
                $expectedAt = $checkedAt;
            }

            // Determine start/finish times if using that feature
            $startedAt = null;
            $finishedAt = null;
            $executionTimeSeconds = null;

            if ($usesStartFinish) {
                // For currently running task (most recent run, status ok, 5% chance)
                if ($i === 0 && $status === 'ok' && $this->faker->boolean(5)) {
                    $startedAt = $checkedAt->copy()->subMinutes($this->faker->numberBetween(2, 30));
                    // No finish - it's currently running!
                } else {
                    // Normal completed run with start/finish
                    $executionTimeSeconds = $this->executionTimeForTask($task->name);
                    $startedAt = $checkedAt;
                    $finishedAt = $checkedAt->copy()->addSeconds($executionTimeSeconds);
                }
            }

            // For hung tasks (alerting with most recent run), create incomplete run
            if ($status === 'alerting' && $i === 0 && $usesStartFinish) {
                $startedAt = $lastCheckIn->copy()->subMinutes($this->faker->numberBetween(60, 240));
                $finishedAt = null;
                $executionTimeSeconds = null;
            }

            TaskRun::create([
                'scheduled_task_id' => $task->id,
                'checked_in_at' => $checkedAt,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'execution_time_seconds' => $executionTimeSeconds,
                'expected_at' => $expectedAt,
                'was_late' => $isLate,
                'lateness_minutes' => $lateness,
                'data' => $this->taskRunPayload($task->name, $isLate),
            ]);
        }
    }

    protected function executionTimeForTask(string $taskName): int
    {
        // Returns execution time in seconds (used for started_at/finished_at)
        if (Str::contains(Str::lower($taskName), 'backup')) {
            return $this->faker->numberBetween(120, 900);
        }

        if (Str::contains(Str::lower($taskName), 'queue')) {
            return $this->faker->numberBetween(30, 240);
        }

        if (Str::contains(Str::lower($taskName), 'etl') || Str::contains(Str::lower($taskName), 'sync')) {
            return $this->faker->numberBetween(180, 1200);
        }

        return $this->faker->numberBetween(20, 300);
    }

    protected function taskRunPayload(string $taskName, bool $isLate): array
    {
        $status = $isLate ? 'warning' : 'success';

        if (Str::contains(Str::lower($taskName), 'backup')) {
            return [
                'size_mb' => $this->faker->numberBetween(5_000, 25_000),
                'status' => $status,
            ];
        }

        if (Str::contains(Str::lower($taskName), 'queue')) {
            return [
                'jobs_processed' => $this->faker->numberBetween(50, 500),
                'status' => $status,
            ];
        }

        if (Str::contains(Str::lower($taskName), 'etl') || Str::contains(Str::lower($taskName), 'sync')) {
            return [
                'records_processed' => $this->faker->numberBetween(10_000, 120_000),
                'status' => $status,
            ];
        }

        return [
            'status' => $status,
        ];
    }

    protected function seedAlerts(ScheduledTask $task, string $status, Collection $teamMembers): void
    {
        if ($status === 'alerting') {
            $missedTriggeredAt = $this->now->copy()->subMinutes($this->faker->numberBetween(30, 240));

            Alert::create([
                'scheduled_task_id' => $task->id,
                'alert_type' => 'missed',
                'triggered_at' => $missedTriggeredAt,
                'message' => "Task '{$task->name}' has missed its scheduled run and exceeded the grace period.",
            ]);

            if ($this->faker->boolean(55)) {
                $ackUser = $teamMembers->random();

                Alert::create([
                    'scheduled_task_id' => $task->id,
                    'alert_type' => 'late',
                    'triggered_at' => $missedTriggeredAt->copy()->subMinutes($this->faker->numberBetween(30, 180)),
                    'message' => "Task '{$task->name}' is running late.",
                    'acknowledged_by' => $ackUser->id,
                    'acknowledged_at' => $missedTriggeredAt->copy()->subMinutes($this->faker->numberBetween(5, 20)),
                ]);
            }

            return;
        }

        if ($status === 'ok' && $this->faker->boolean(25)) {
            $ackUser = $teamMembers->random();
            $recoveredAt = $this->now->copy()->subDays($this->faker->numberBetween(3, 14))->setTime(
                $this->faker->numberBetween(6, 18),
                $this->faker->numberBetween(0, 59)
            );

            Alert::create([
                'scheduled_task_id' => $task->id,
                'alert_type' => 'recovered',
                'triggered_at' => $recoveredAt,
                'message' => "Task '{$task->name}' has recovered and is checking in again.",
                'acknowledged_by' => $ackUser->id,
                'acknowledged_at' => $recoveredAt->copy()->addMinutes($this->faker->numberBetween(5, 30)),
            ]);
        }

        if ($status === 'paused' && $this->faker->boolean(12)) {
            Alert::create([
                'scheduled_task_id' => $task->id,
                'alert_type' => 'missed',
                'triggered_at' => $this->now->copy()->subDays($this->faker->numberBetween(15, 40)),
                'message' => "Task '{$task->name}' was paused following repeated misses.",
            ]);
        }
    }

    protected function seedAuditLogs(Collection $users, Collection $centralTeams, Collection $schoolTeams): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $tasks = ScheduledTask::with('team', 'creator')->get();

        if ($tasks->isEmpty()) {
            return;
        }

        $teams = $centralTeams->merge($schoolTeams)->values();

        if ($teams->isEmpty()) {
            $teams = Team::with('users')->get();
        }

        $alerts = Alert::with('scheduledTask.team')->get();

        $generators = [
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} created scheduled task \"{$task->name}\" for {$task->team->name}.";
            },
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} updated the schedule for \"{$task->name}\" to {$this->auditScheduleSummary($task)}.";
            },
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} adjusted the grace period for \"{$task->name}\" to {$task->grace_period_minutes} minutes.";
            },
            function (User $user, ScheduledTask $task) {
                return $this->faker->boolean(55)
                    ? "{$user->full_name} paused \"{$task->name}\" for a planned maintenance window."
                    : "{$user->full_name} resumed monitoring for \"{$task->name}\" after maintenance.";
            },
            function (User $user, ScheduledTask $task) {
                $hours = $this->faker->numberBetween(1, 12);

                return "{$user->full_name} silenced alerts for \"{$task->name}\" for {$hours} hours.";
            },
            function (User $user, ScheduledTask $task) use ($teams, $users) {
                $team = $teams->random();
                $memberName = $this->pickTeamMemberName($team, $users);

                return "{$user->full_name} invited {$memberName} to {$team->name}.";
            },
            function (User $user, ScheduledTask $task) use ($alerts) {
                $alert = $alerts->isNotEmpty() ? $alerts->random() : null;
                $targetTask = $alert?->scheduledTask ?? $task;
                $type = ucfirst($alert?->alert_type ?? 'missed');

                return "{$user->full_name} acknowledged the {$type} alert for \"{$targetTask->name}\".";
            },
            function (User $user, ScheduledTask $task) use ($teams) {
                $team = $teams->random();

                return "{$user->full_name} exported a status report for {$team->name}.";
            },
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} rotated an API token used by \"{$task->name}\" integrations.";
            },
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} triggered a manual check-in for \"{$task->name}\".";
            },
            function (User $user, ScheduledTask $task) {
                return "{$user->full_name} updated the timezone for \"{$task->name}\" to {$task->timezone}.";
            },
            function (User $user, ScheduledTask $task) use ($teams) {
                $destinationTeam = $teams->random();
                $teamName = $destinationTeam->name === $task->team->name
                    ? "{$task->team->name} (verified)"
                    : $destinationTeam->name;

                return "{$user->full_name} reviewed team ownership for \"{$task->name}\" and confirmed {$teamName}.";
            },
        ];

        $records = [];

        for ($i = 0; $i < 2000; $i++) {
            $user = $users->random();
            $task = $tasks->random();
            $generator = Arr::random($generators);
            $message = $generator($user, $task);

            $timestamp = Carbon::instance($this->faker->dateTimeBetween('-120 days', 'now', 'UTC'));

            $records[] = [
                'message' => $message,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($records) === 500) {
                AuditLog::insert($records);
                $records = [];
            }
        }

        if (! empty($records)) {
            AuditLog::insert($records);
        }
    }

    protected function auditScheduleSummary(ScheduledTask $task): string
    {
        if ($task->schedule_type === 'cron') {
            return "cron expression {$task->schedule_value}";
        }

        return match ($task->schedule_value) {
            '5m' => 'every 5 minutes',
            '15m' => 'every 15 minutes',
            '30m' => 'every 30 minutes',
            '1h' => 'hourly',
            '6h' => 'every 6 hours',
            '12h' => 'every 12 hours',
            'daily' => 'daily',
            default => $task->schedule_value,
        };
    }

    protected function pickTeamMemberName(Team $team, Collection $users): string
    {
        if ($team->relationLoaded('users') && $team->users->isNotEmpty()) {
            return $team->users->random()->full_name;
        }

        return $users->random()->full_name;
    }

    protected function timezoneForContext(string $context): string
    {
        return $context === 'central'
            ? Arr::random(['Europe/London', 'UTC'])
            : Arr::random(['Europe/London', 'UTC', 'Europe/Paris']);
    }

    protected function applySilencing(Collection $schoolTeams, array $createdTasks): void
    {
        $schoolTeams = $schoolTeams->values();

        if ($schoolTeams->count() > 2) {
            $team = $schoolTeams->get(2);

            if ($team !== null) {
                $team->alerts_silenced_until = $this->now->copy()->addHours(12);
                $team->save();
            }
        }

        $taskPool = $createdTasks['alerting']->merge($createdTasks['ok']);

        if ($taskPool->isEmpty()) {
            return;
        }

        $taskPool->random(min(6, $taskPool->count()))->each(function (ScheduledTask $task) {
            $task->alerts_silenced_until = $this->now->copy()->addHours($this->faker->numberBetween(2, 8));
            $task->save();
        });
    }
}
