# CronMon

CronMon is a monitoring and alerting application for scheduled tasks like cron jobs and Windows Task Scheduler. If your scheduled tasks fail to run or are late, CronMon detects the problem and sends email alerts to your team.

The system works with a simple ping-based approach: your scheduled tasks send a quick HTTP request to CronMon when they complete. If CronMon doesn't receive the expected ping within the configured schedule and grace period, it triggers an alert. This makes it straightforward to monitor any scheduled task across Linux, Windows, or any other platform that can make HTTP requests.

CronMon is designed for IT teams managing infrastructure across multiple departments. It supports team-based organization, audit logging, and bulk import/export of task configurations for moving setups between environments.

## Features

- **Simple ping-based monitoring** - Tasks check in via HTTP GET/POST requests to a unique URL
- **Flexible scheduling** - Supports both simple intervals (5m, 1h, daily) and cron expressions
- **Smart alerting** - Detects missed and late tasks with configurable grace periods
- **Email notifications** - Alerts are sent to all team members when tasks fail or recover
- **Team-based organization** - Tasks belong to teams with shared access for collaboration
- **Execution time tracking** - Optional start/finish pings to monitor task duration and detect hung jobs
- **Dashboard** - At-a-glance view of task health across all teams
- **Alert management** - Acknowledge alerts and silence notifications during maintenance windows
- **Audit logging** - Complete history of configuration changes and user actions
- **Import/Export** - Migrate task configurations between environments (test to production)
- **Personal and shared teams** - Each user gets a personal workspace plus access to shared teams

## Tech Stack

- **Laravel 12** - PHP framework
- **Livewire 3** - Dynamic interfaces
- **Flux UI Pro** - Component library (Tailwind CSS + Vite)
- **Lando** - Local development environment

## Getting Started

### Prerequisites

- [Lando](https://lando.dev/) installed on your machine
- Git

### Installation

1. Clone the repository:
```bash
git clone git@github.com:College-of-Science-Engineering/cronmon.git
cd cronmon
```

2. Set up environment and dependencies:
```bash
cp .env.example .env
composer install
npm install
npm run build
```

3. Start Lando and set up the database:
```bash
lando start
# If this is your first run, lando start may error due to missing DB tables
lando mfs  # Migrate and seed the database
```

4. Access the application at the URL shown by `lando info` (typically https://cronmon.lndo.site)

### Default Login

After running the seeder, you can log in with:
- **Username:** `admin2x`
- **Password:** `secret`

The seeder creates 30 test users across multiple teams with 500 scheduled tasks and realistic check-in history.

### Development

- **Start Lando:** `lando start`
- **Migrate and Seed database:** `lando mfs`
- **Install dependencies:** `lando composer install` / `lando npm install`
- **Build assets:** `lando npm run build`
- **Watch assets:** `lando npm run dev`
- **Run tests:** `lando artisan test`
- **Format code:** `lando vendor/bin/pint`

### Common Lando Commands

- `lando artisan [command]` - Run Laravel artisan commands
- `lando composer [command]` - Run Composer commands
- `lando npm [command]` - Run npm commands
- `lando mysql` - Access MySQL shell
- `lando mfs` - Custom command to migrate fresh and seed (uses `TestDataSeeder`)
- `lando test` - Run the test suite

## Project Structure

- `app/Models/` - Eloquent models (User, Team, ScheduledTask, TaskRun, Alert, AuditLog)
- `app/Livewire/` - Livewire components for the UI
- `app/Services/` - Business logic (ScheduleCalculator, ImportService, ExportService)
- `app/Console/Commands/` - Artisan commands (CheckMissedTasks for background monitoring)
- `resources/views/livewire/` - Blade templates for Livewire components
- `routes/web.php` - Application routes
- `routes/console.php` - Scheduled command configuration
- `database/migrations/` - Database schema migrations
- `database/seeders/TestDataSeeder.php` - Test data generator for local development
- `tests/Feature/` - Feature tests (233 tests, 558 assertions)

## How It Works

### Task Check-In

Each scheduled task gets a unique check-in URL when created:

```bash
# Simple check-in (GET or POST)
curl https://cronmon.example.com/ping/abc123-token-here

# Check-in with custom data
curl -X POST https://cronmon.example.com/ping/abc123-token-here \
  -H "Content-Type: application/json" \
  -d '{"jobs_processed": 150, "status": "success"}'

# Track execution time (start and finish pings)
curl https://cronmon.example.com/ping/abc123-token-here?start
# ... your task runs ...
curl https://cronmon.example.com/ping/abc123-token-here?finish
```

Add the ping to your crontab:

```bash
0 3 * * * /usr/local/bin/backup-database.sh && curl -X GET https://cronmon.example.com/ping/abc123-token-here
```

### Background Monitoring

CronMon runs a background command every minute (`tasks:check-missed`) that:
1. Calculates when each task should have checked in based on its schedule
2. Compares expected time against actual last check-in time
3. Creates alerts and sends emails when tasks are missed or late
4. Creates recovery alerts when previously-failing tasks check in again

### Teams and Access Control

- Each user automatically gets a personal team (named after their username)
- Teams can have multiple members with equal access to the team's tasks
- All authenticated users can view all teams and tasks
- Only team members can modify tasks and team settings
- This trust-based model is designed for small IT teams

## Testing

CronMon has comprehensive test coverage with 233 tests covering:

- Ping API endpoint (public check-ins)
- Background missed task detection
- Alert generation and email notifications
- Dashboard and task management UI
- Team management and authorization policies
- Import/Export functionality
- Schedule calculation (simple intervals and cron expressions)
- Start/finish execution time tracking

Run the full test suite:

```bash
lando artisan test
```

Run specific tests:

```bash
lando artisan test --filter=PingApi
lando artisan test tests/Feature/ImportExport/
```

## Contributing

This project follows Laravel conventions and uses Laravel Pint for code style enforcement. Before submitting changes:

1. Write feature tests for new functionality
2. Run the test suite: `lando artisan test`
3. Format code: `lando vendor/bin/pint`

## License

This project is licensed under the MIT License.
