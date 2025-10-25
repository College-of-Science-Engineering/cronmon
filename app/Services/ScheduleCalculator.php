<?php

namespace App\Services;

use Carbon\Carbon;
use Cron\CronExpression;

class ScheduleCalculator
{
    public function calculateNextExpectedTime(string $scheduleType, string $scheduleValue, Carbon $lastCheckedIn): Carbon
    {
        if ($scheduleType === 'simple') {
            return $this->calculateSimpleSchedule($scheduleValue, $lastCheckedIn);
        }

        return $this->calculateCronSchedule($scheduleValue, $lastCheckedIn);
    }

    public function isTaskLate(string $scheduleType, string $scheduleValue, ?Carbon $lastCheckedIn, int $gracePeriodMinutes, Carbon $now): bool
    {
        if ($lastCheckedIn === null) {
            return false; // Can't be late if never checked in
        }

        $nextExpected = $this->calculateNextExpectedTime($scheduleType, $scheduleValue, $lastCheckedIn);
        $latenessDeadline = $nextExpected->copy()->addMinutes($gracePeriodMinutes);

        return $now->isAfter($latenessDeadline);
    }

    public function calculateLateness(string $scheduleType, string $scheduleValue, Carbon $lastCheckedIn, Carbon $now): int
    {
        $nextExpected = $this->calculateNextExpectedTime($scheduleType, $scheduleValue, $lastCheckedIn);

        return (int) $nextExpected->diffInMinutes($now);
    }

    protected function calculateSimpleSchedule(string $scheduleValue, Carbon $lastCheckedIn): Carbon
    {
        $intervals = [
            '5m' => 5,
            '15m' => 15,
            '30m' => 30,
            '1h' => 60,
            '6h' => 360,
            '12h' => 720,
            'daily' => 1440,
        ];

        $minutes = $intervals[$scheduleValue] ?? 60; // Default to 1 hour if unknown

        return $lastCheckedIn->copy()->addMinutes($minutes);
    }

    protected function calculateCronSchedule(string $cronExpression, Carbon $lastCheckedIn): Carbon
    {
        $cron = new CronExpression($cronExpression);
        $nextRun = $cron->getNextRunDate($lastCheckedIn->toDateTime());

        return Carbon::instance($nextRun);
    }
}
