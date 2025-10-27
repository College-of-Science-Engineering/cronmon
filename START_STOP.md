# Start/Stop Tracking Implementation Plan

## Overview
Adding optional `?start` and `?finish` query parameters to the ping API endpoint to track task execution timing.

## Key Design Decisions
- Query parameters only (no timestamps in body)
- Server generates timestamps automatically (admin-friendly)
- Start ping does NOT update `last_checked_in_at` or task status
- Only finish ping marks task as complete
- Hung jobs (start without finish) detected by existing missed task logic
- Finish without start accepted as normal check-in (backward compatible)

## Implementation Checklist

### Database & Models
- [x] Create migration adding `started_at`, `finished_at`, `execution_time_seconds` to `task_runs` table
- [x] Update TaskRun model casts for new timestamp columns
- [x] Add helper methods to TaskRun: `isRunning()`, `isComplete()`, `executionTime()`
- [x] Update TaskRunFactory to handle new columns
- [x] Add `currentlyRunningTaskRun()` helper method to ScheduledTask model

### API Logic
- [x] Update PingController to parse `?start` and `?finish` query parameters
- [x] Implement start logic: create TaskRun with started_at, don't update task status
- [x] Implement finish logic: find incomplete TaskRun and complete it, OR create new one, update task status
- [x] Ensure plain ping (no params) still works (backward compatibility)
- [x] Update RecordTaskCheckIn job if needed for new logic

### UI Updates - Show Page
- [x] Add "Currently Running" badge in hero section when incomplete TaskRun exists
- [x] Update status callout to reflect hung jobs ("started but never finished")
- [x] Add "Running..." badge in History tab for incomplete TaskRuns
- [x] Show execution time for completed runs in History tab
- [x] Consider chart updates to show execution time trends

### UI Updates - Other Pages (Optional Polish)
- [ ] Consider showing "Running" badge in Index task list for tasks with incomplete runs
- [ ] Consider showing running status on Dashboard

### API Documentation
- [x] Add `?start` and `?finish` examples to task show page API tab
- [x] Update Quick Start section with optional parameters
- [x] Show example bash script with start at top, finish at bottom

### Testing
- [x] Test: start ping creates incomplete TaskRun
- [x] Test: finish ping completes existing TaskRun and calculates execution time
- [x] Test: finish without start creates normal TaskRun
- [x] Test: plain ping still works (backward compatibility)
- [x] Test: multiple starts without finish (only most recent matters)
- [x] Test: hung job detection (start without finish triggers alerts via CheckMissedTasks)
- [x] Test: TaskRun helper methods (isRunning, isComplete, executionTime)
- [x] Test: ScheduledTask currentlyRunningTaskRun() method
- [x] Test: UI displays running status correctly on Show page
- [x] Update existing ping endpoint tests as needed
- [x] Update seeder with realistic start/finish data

### Code Quality
- [x] Run `vendor/bin/pint --dirty` for code formatting
- [x] Run full test suite to ensure no regressions (211 tests, 489 assertions - all passing!)
- [x] Manual browser testing of Show page with running/hung tasks

### Bug Fixes
- [x] Fix chart data ordering - was showing oldest 30 runs instead of most recent 30 (Show.php:52)
- [x] Fix chart data format - added `->values()` to re-index collection after `->reverse()` to ensure proper JavaScript array (Show.php:56)
- [x] Fix chart X-axis clustering - changed from date labels to run numbers (1-30) to spread points across chart, moved full date/time to tooltip (Show.php:64-69, show.blade.php:55,66)

## Implementation Notes

### API Behavior Summary

**Start ping: `/ping/{token}?start`**
- Creates TaskRun with `started_at` = now, `finished_at` = null
- Does NOT update `task.last_checked_in_at`
- Does NOT change task status
- Task remains in current state until finish arrives

**Finish ping: `/ping/{token}?finish`**
- Finds most recent incomplete TaskRun and updates `finished_at` = now
- Calculates `execution_time_seconds` = (finished_at - started_at)
- Updates `task.last_checked_in_at` = now
- Sets `task.status` = 'ok'

**Finish without start:**
- Creates TaskRun with `finished_at` = now, `started_at` = null
- Normal check-in behavior (backward compatible)

**Plain ping: `/ping/{token}`**
- Current behavior unchanged
- Creates TaskRun with `checked_in_at` = now
- Updates task status to 'ok'

### Hung Job Detection
- Existing `CheckMissedTasks` command handles this automatically
- If start ping arrives but finish never comes, `last_checked_in_at` won't update
- When `next_expected_at + grace_period` passes, alert is triggered
- UI shows "started but never finished" using `currentlyRunningTaskRun()` helper

### Example Bash Script
```bash
#!/bin/bash
curl https://cronmon.example.com/ping/TOKEN?start

# Run backup
pg_dump mydb > backup.sql

curl https://cronmon.example.com/ping/TOKEN?finish
```
