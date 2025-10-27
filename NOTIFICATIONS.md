# AlertManager Integration Plan

## Overview
Add support for Prometheus AlertManager integration to handle email deduplication and rate limiting for task alerts, preventing email spam while ensuring critical notifications are never lost.

## Key Design Decisions
- Use Laravel custom notification channel for AlertManager
- Store team email addresses (primary and secondary) on teams table
- Implement Redis-based rate limiting per task_id
- Support dual channel: both direct email and AlertManager
- Make AlertManager configurable (can be disabled)
- **Always rate limit emails** regardless of AlertManager status
- Simplify channel selection by starting with `['mail']` array

## Implementation Steps

### 1. Configuration Setup
- Add AlertManager configuration to `config/cronmon.php`:
  - `alertmanager.enabled` (boolean)
  - `alertmanager.url` (string)
  - `alertmanager.timeout` (integer, default 5 seconds)
  - `email_rate_limit_minutes` (integer, default 30)

### 2. Database & Models
- Create migration to add email fields to teams table:
  - `primary_email` (nullable string)
  - `secondary_email` (nullable string)
- Make Team model `Notifiable`
- Add `routeNotificationForMail()` method to Team model returning array of email addresses

### 3. Custom Notification Channel
- Create `AlertManagerChannel` class in `app/Notifications/Channels/`
- Implement `send()` method to POST alerts to AlertManager API
- Handle HTTP errors gracefully (log but don't throw)
- Format alert payload with labels: task_id, task_name, team_id, alert_type, severity

### 4. Notification Classes
- Refactor existing alert notifications to:
  - Target Team instead of individual users
  - Implement simplified `via()` method with rate limiting:
    ```php
    public function via($notifiable): array
    {
        $channels = ['mail'];

        if (config('cronmon.alertmanager.enabled')) {
            $channels[] = 'alertmanager';
        }

        $redisKey = "cronmon:alert:ratelimit:{$this->task->id}";
        if (Cache::has($redisKey)) {
            $channels = array_diff($channels, ['mail']);
        } else {
            Cache::put($redisKey, now()->timestamp, now()->addMinutes(config('cronmon.email_rate_limit_minutes')));
        }

        return $channels;
    }
    ```
  - Add `toAlertManager()` method returning alert payload

### 5. CheckMissedTasks Command
- Update to send notifications to `$task->team` instead of individual users
- Ensure all alert types (missed, late, recovered) use the new notification approach

### 6. Team Management UI
- Add email fields to team creation/edit forms
- Validate email format for both fields
- Show current AlertManager status in team settings (enabled/disabled badge)

### 7. Testing
- Test custom AlertManager channel with mock HTTP client
- Test rate limiting prevents duplicate emails within window
- Test rate limiting resets after window expires
- Test dual channel delivery when AlertManager enabled
- Test fallback to mail-only when AlertManager disabled
- Test notifications sent to correct team emails
- Test graceful handling of AlertManager API failures
- Update existing notification tests for new Team-based approach

### 8. Documentation
- Update PROJECT_PLAN.md with AlertManager integration details
- Add example AlertManager configuration to `.env.example`
- Document team email configuration in UI

## Benefits
- Prevents email spam from repeatedly failing jobs
- Leverages AlertManager's built-in grouping and deduplication
- Maintains direct email delivery as fallback
- Configurable for organizations of different sizes
- Rate limiting provides protection even without AlertManager

## Migration Path
- Existing installations work immediately (AlertManager disabled by default)
- Teams can optionally configure emails and enable AlertManager
- No breaking changes to existing alert functionality
