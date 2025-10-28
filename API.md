# Admin API

We have Laravel Sanctum installed.

- We need a way to create Sanctum keys for each user.
- There is currently an unused 'Settings' link on the main resources/views/components/layouts/app.blade.php which could be used to manage these

## API Endpoints (behind Sanctum bearer-token authentication)

(Note: we have https://spatie.be/docs/laravel-query-builder/v6/features/filtering installed in the project)

- Get all tasks (with date range filter)
- Get all teams
- Create new task
- Silence task
- Silence team

## Admin API Progress

- [x] Protect `/api/v1` routes with Sanctum bearer auth and policy checks.
- [x] Implement task listing with Query Builder filters (`status`, `team_id`, `checked_between`) and includes.
- [x] Support task creation via validated form request and resource transformer.
- [x] Provide task/team silencing endpoints returning enriched resources.
- [x] Ship API resources and feature tests for tasks and teams.
- [x] Build Settings UI for managing personal access tokens (generate, display, revoke).
- [x] Add task update/delete endpoints plus associated validation and tests.
- [x] Add `filter[silenced]` support for tasks; evaluate further filters (status history, etc.) as clients need them.

## API Docs

### Creating an API Token

1. Sign in and open **Settings » Tokens** from the sidebar.
2. Enter a descriptive name (for example, `Production Deploy`), then click **Generate token**.
3. Copy the plain-text token immediately – it will not be shown again once you hide the callout.
4. Store the token securely (1Password, Bitwarden, etc.). Revoke it from the same screen when no longer needed.

All requests must send the token as a bearer credential:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://app.example.com/api/v1/tasks
```

### Example Requests

List tasks (with optional filters):

```bash
curl \
  -H "Authorization: Bearer YOUR_TOKEN" \
  "https://app.example.com/api/v1/tasks?include=team&filter[status]=alerting&filter[silenced]=false"
```

Create a task:

```bash
curl \
  -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
        "team_id": 1,
        "name": "Nightly Backup",
        "description": "Runs every night at midnight",
        "schedule_type": "cron",
        "schedule_value": "0 0 * * *",
        "timezone": "UTC",
        "grace_period_minutes": 15
      }' \
  https://app.example.com/api/v1/tasks
```

Update a task:

```bash
curl \
  -X PUT \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
        "team_id": 1,
        "name": "Nightly Backup",
        "description": "Runs every night at 1am UTC",
        "schedule_type": "cron",
        "schedule_value": "0 1 * * *",
        "timezone": "UTC",
        "grace_period_minutes": 20
      }' \
  https://app.example.com/api/v1/tasks/42
```

Delete a task:

```bash
curl \
  -X DELETE \
  -H "Authorization: Bearer YOUR_TOKEN" \
  https://app.example.com/api/v1/tasks/42
```

Silence a task for one hour:

```bash
curl \
  -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"silenced_until": "$(date -u -v+1H +%Y-%m-%dT%H:%M:%SZ)"}' \
  https://app.example.com/api/v1/tasks/42/silence
```

List teams the user belongs to:

```bash
curl \
  -H "Authorization: Bearer YOUR_TOKEN" \
  "https://app.example.com/api/v1/teams?include=scheduled_tasks"
```
