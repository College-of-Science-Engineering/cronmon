<?php

use App\Services\ScheduleCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = app(ScheduleCalculator::class);
});

// Simple Schedule Parsing Tests

it('calculates next expected time for 5 minute interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '5m', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:05:00');
});

it('calculates next expected time for 15 minute interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '15m', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:15:00');
});

it('calculates next expected time for 30 minute interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '30m', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:30:00');
});

it('calculates next expected time for 1 hour interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '1h', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 11:00:00');
});

it('calculates next expected time for 6 hour interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '6h', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 16:00:00');
});

it('calculates next expected time for 12 hour interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', '12h', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 22:00:00');
});

it('calculates next expected time for daily interval', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('simple', 'daily', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-16 10:00:00');
});

// Cron Schedule Parsing Tests

it('calculates next expected time for cron expression running every hour', function () {
    // Arrange - Cron: 0 * * * * (every hour at minute 0)
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('cron', '0 * * * *', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 11:00:00');
});

it('calculates next expected time for cron expression running daily at 3am', function () {
    // Arrange - Cron: 0 3 * * * (daily at 3:00 AM)
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('cron', '0 3 * * *', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-16 03:00:00');
});

it('calculates next expected time for cron expression running every 15 minutes', function () {
    // Arrange - Cron: */15 * * * * (every 15 minutes)
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');

    // Act
    $nextExpected = $this->calculator->calculateNextExpectedTime('cron', '*/15 * * * *', $lastCheckedIn);

    // Assert
    expect($nextExpected->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:15:00');
});

// Task Lateness Detection Tests

it('detects task is on time', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');
    $now = Carbon::parse('2025-01-15 10:04:00'); // 4 minutes after expected
    $gracePeriod = 10; // 10 minute grace period

    // Act
    $isLate = $this->calculator->isTaskLate('simple', '5m', $lastCheckedIn, $gracePeriod, $now);

    // Assert
    expect($isLate)->toBeFalse();
});

it('detects task is late within grace period', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');
    $now = Carbon::parse('2025-01-15 10:10:00'); // 5 minutes late but within 10 min grace
    $gracePeriod = 10;

    // Act
    $isLate = $this->calculator->isTaskLate('simple', '5m', $lastCheckedIn, $gracePeriod, $now);

    // Assert
    expect($isLate)->toBeFalse();
});

it('detects task is late beyond grace period', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');
    $now = Carbon::parse('2025-01-15 10:20:00'); // 15 minutes late, beyond 10 min grace
    $gracePeriod = 10;

    // Act
    $isLate = $this->calculator->isTaskLate('simple', '5m', $lastCheckedIn, $gracePeriod, $now);

    // Assert
    expect($isLate)->toBeTrue();
});

it('calculates lateness in minutes', function () {
    // Arrange
    $lastCheckedIn = Carbon::parse('2025-01-15 10:00:00');
    $now = Carbon::parse('2025-01-15 10:20:00'); // 15 minutes late

    // Act
    $lateness = $this->calculator->calculateLateness('simple', '5m', $lastCheckedIn, $now);

    // Assert
    expect($lateness)->toBe(15);
});

it('handles task that has never checked in', function () {
    // Arrange
    $lastCheckedIn = null;
    $now = Carbon::parse('2025-01-15 10:20:00');
    $gracePeriod = 10;

    // Act
    $isLate = $this->calculator->isTaskLate('simple', '5m', $lastCheckedIn, $gracePeriod, $now);

    // Assert
    expect($isLate)->toBeFalse(); // Can't be late if never checked in
});
