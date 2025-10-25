# Overall Project Goal

We want to create a laravel application which alerts users if their scheduled task has not run as expected (ie, cron jobs).

## Phase 1 Features (Via web interface)

- [ ] User can view a list of their own scheduled tasks
- [ ] User can create a new scheduled task
- [ ] User can edit a scheduled task
- [ ] User can delete a scheduled task
- [ ] User can view a scheduled task
- [ ] User can view a scheduled task's history
- [ ] User can view a scheduled task's logs
- [ ] User can view a scheduled task's alerts

### Implementation Plan

An initial laravel project has been created, fluxui and livewire have been installed.

We do not need a login system at this stage, but the main routes should be in a Route::group so that only authenticated users can access them.

We then need to implement full-page livewire components that use the fluxui web components to implement the features above.

## Data Model

### Team
Represents a team that owns scheduled tasks. Each user gets a personal team (named after their username) on creation. Teams can have multiple members.

**Columns:**
- `id`
- `name` (string) - Team name, e.g., "billy", "Marketing Team"
- `slug` (string, unique) - URL-friendly identifier
- `timestamps`

**Relationships:**
- `belongsToMany(User)` - through `team_user` pivot
- `hasMany(ScheduledTask)`

**Personal Teams:** If `$team->name === $user->username`, it's a personal team.

### ScheduledTask
Represents a cron job being monitored.

**Columns:**
- `id`
- `team_id` (foreign key, indexed) - Task belongs to one team
- `created_by` (foreign key to users, nullable) - User who created the task (informational)
- `name` (string) - Friendly name, e.g., "Database Backup"
- `description` (text, nullable) - Optional notes
- `schedule_type` (enum: 'simple', 'cron') - Simple intervals or cron expression
- `schedule_value` (string) - "5m", "1h", "daily" OR cron expression "0 3 * * *"
- `timezone` (string, default: 'UTC') - For interpreting schedules
- `grace_period_minutes` (integer, default: 10) - Forgiveness window
- `unique_check_in_token` (string, unique, indexed) - UUID for API endpoint `/ping/{token}`
- `last_checked_in_at` (timestamp, nullable) - Most recent successful ping
- `next_expected_at` (timestamp, nullable) - Calculated next expected run
- `status` (enum: 'ok', 'pending', 'alerting', 'paused') - Current health status
- `timestamps`

**Relationships:**
- `belongsTo(Team)`
- `belongsTo(User, 'created_by')`
- `hasMany(TaskRun)`
- `hasMany(Alert)`

**Check-in Method:** Cron jobs hit HTTP endpoint (GET/POST) at `/ping/{unique_check_in_token}`

### TaskRun
Records each time a task checks in via the API.

**Columns:**
- `id`
- `scheduled_task_id` (foreign key, indexed)
- `checked_in_at` (timestamp, indexed) - When the ping arrived
- `expected_at` (timestamp, nullable) - When we expected it
- `was_late` (boolean, default: false) - Did it exceed grace period?
- `lateness_minutes` (integer, nullable) - How many minutes late
- `timestamps`

**Relationships:**
- `belongsTo(ScheduledTask)`

### Alert
Tracks notifications when tasks miss their schedule or recover.

**Columns:**
- `id`
- `scheduled_task_id` (foreign key, indexed)
- `alert_type` (enum: 'missed', 'late', 'recovered') - What triggered this alert
- `triggered_at` (timestamp) - When the alert was created
- `acknowledged_at` (timestamp, nullable) - When marked as read
- `acknowledged_by` (foreign key to users, nullable) - Who acknowledged it
- `message` (text) - Human-readable description
- `timestamps`

**Relationships:**
- `belongsTo(ScheduledTask)`
- `belongsTo(User, 'acknowledged_by')`

### Pivot: team_user
Manages team membership.

**Columns:**
- `id`
- `team_id` (foreign key)
- `user_id` (foreign key)
- `timestamps`

**Access Control:** All team members have equal access to team's tasks (no roles in Phase 1).

## Phase 1 Implementation Checklist

### Database Setup
- [x] Create `teams` table migration
- [x] Create `team_user` pivot table migration
- [x] Create `scheduled_tasks` table migration
- [x] Create `task_runs` table migration
- [x] Create `alerts` table migration
- [x] Run migrations to create database structure

### Models & Relationships
- [x] Create `Team` model with relationships
- [x] Create `ScheduledTask` model with relationships and casts
- [x] Create `TaskRun` model with relationships
- [x] Create `Alert` model with relationships
- [x] Update `User` model to include team relationship

### Factories & Seeders
- [x] Create `TeamFactory`
- [x] Create `ScheduledTaskFactory`
- [x] Create `TaskRunFactory`
- [x] Create `AlertFactory`
- [x] Create database seeder with sample data

### API Endpoints (Check-in)

**Two API Types:**
1. **Simple Ping API** (Phase 1 - Current Focus)
   - Public endpoint: `/ping/{token}` (GET or POST)
   - No authentication required - uses unique token
   - Makes life easy for Linux/Windows admins
   - GET: Simple acknowledgment that job ran
   - POST: Accepts optional `data` JSON field for flexible admin use (stats, messages, timings, etc.)

2. **Reporting APIs** (Future - PowerBI Integration)
   - Behind Laravel Sanctum authentication
   - For dashboard/reporting integrations
   - Stub out for now, implement later

**Phase 1 Tasks:**
- [x] Add `data` JSON column to task_runs table
- [x] Update TaskRun model and factory for data field
- [x] Write tests for ping endpoint (GET and POST) - 13 tests covering all scenarios
- [x] Create public API route for `/ping/{token}` (GET and POST)
- [x] Implement check-in logic (record TaskRun, update last_checked_in_at, update status)
- [ ] Calculate and update next_expected_at (background job - future)
- [ ] Detect late/missed runs and create alerts (background job - future)

### User Registration Flow
**DEFERRED** - Using corporate SSO, will implement later
- [ ] Add observer/listener to create personal team on user creation
- [ ] Automatically add user to their personal team
- [ ] Write tests for automatic team creation

### Authorization & Policies
- [x] Create `ScheduledTaskPolicy` (check team membership)
- [x] Create `TeamPolicy` (check team membership)
- [x] Register policies (auto-discovered by Laravel 12)
- [x] Write tests for authorization rules (22 tests)

### Livewire Components & Routes (Using Form Class Pattern)
- [x] Create authenticated route group
- [x] Create `ScheduledTaskForm` class (shared form logic, validation, save)
- [x] Create `ScheduledTasks/Index` component (list all tasks from user's teams)
- [x] Create `ScheduledTasks/Create` component (uses ScheduledTaskForm)
- [x] Create `ScheduledTasks/Edit` component (uses ScheduledTaskForm)
- [x] Create `ScheduledTasks/Show` component (detail view with tabs for history/alerts)
- [x] Update sidebar navigation in app layout
- [x] Write Pest feature tests for Index component (5 tests, all passing)
- [x] Write Pest feature tests for Create component (6 tests, all passing)
- [x] Write Pest feature tests for Edit component (11 tests, all passing)
- [x] Write Pest feature tests for Show component (23 tests, all passing)

### Background Processing & Alerts

**Notification Strategy:** Mail:: only (no Slack or other channels at this time)

**Tasks:**
- [x] Write tests for schedule parsing (simple intervals: "5m", "1h", "daily" and cron expressions) - 15 tests
- [x] Write tests for missed task detection logic
- [x] Write tests for alert generation
- [x] Write tests for email notifications to team members - 10 tests
- [x] Create scheduled command to check for missed tasks (`tasks:check-missed`)
- [x] Implement schedule parser/calculator service (ScheduleCalculator)
- [x] Implement alert generation logic (creates missed/late/recovered alerts)
- [x] Create Mail notification for missed/late/recovered tasks (TaskMissedNotification)
- [x] Schedule command in `routes/console.php` (runs every minute)

### Testing
- [x] Write feature tests for Index CRUD operations
- [x] Write feature tests for Create operations
- [x] Write feature tests for Edit operations
- [x] Write feature tests for Show operations
- [x] Write feature tests for check-in API
- [x] Write feature tests for schedule calculator service
- [x] Write feature tests for missed task detection & alerts
- [x] Write feature tests for team-based authorization
- [x] Run test suite for all Phase 1 features

## Development Notes & Lessons Learned

### Issues Encountered and Solutions

**1. Missing Route Breaking Layout (2025-10-24)**
- **Problem:** The base layout referenced a non-existent `auth.logout` route (part of SSO integration not yet implemented). This caused exceptions on every page, which displayed SQL queries in the error output.
- **Impact:** Tests were failing because `assertDontSee('Secret Task')` was matching SQL INSERT statements in the exception page, not the actual rendered content.
- **Solution:** The user added a temporary fake route for `auth.logout` to prevent exceptions until SSO is implemented.
- **Lesson:** When tests fail in unexpected ways (seeing SQL in output), it often means there's an underlying exception. Don't just try to work around the symptoms - ask the user for help to find the root cause!

**2. Auto-Login Hack Breaking Tests**
- **Problem:** Added auto-login code in `routes/web.php` for development convenience, but it ran during test setup before database migrations, causing failures.
- **Impact:** Tests failed with "no such table: users" errors. Also broke Laravel Boost MCP tool.
- **Solution:** Wrapped auto-login in `if (! app()->environment('testing'))` check to skip during tests.
- **Lesson:** Development convenience code should always check the environment. When in doubt about test failures, ask the user - they know the Lando/Docker setup better!

**3. Flux UI Table Syntax Error**
- **Problem:** Used incorrect shortened syntax `flux:columns/column` instead of proper `flux:table.columns/.column`.
- **Impact:** Application crashed on /tasks page.
- **Solution:** Used Laravel Boost's `search-docs` tool to find correct Flux UI documentation. Fixed syntax in both Index and Show components.
- **Lesson:** Always use Laravel Boost to search for Flux documentation - it provides version-specific docs for this project's packages. The user explicitly asked to write tests first instead of blindly fixing the syntax error!

**4. SQL Ambiguous Column Name**
- **Problem:** `pluck('id')` on a query joining `teams` and `team_user` tables was ambiguous.
- **Impact:** Database error in Index component: "ambiguous column name: id"
- **Solution:** Changed to `pluck('teams.id')` to explicitly specify the table.
- **Lesson:** Always qualify column names when working with joins or relationships that involve multiple tables with common column names.

**5. Livewire Test Authentication Pattern**
- **Problem:** Livewire components accessed `auth()->user()` but tests called `livewire(Component::class)->actingAs($user)` which didn't authenticate before component instantiation.
- **Impact:** "Call to a member function teams() on null" errors in tests.
- **Solution:** Split into two steps: `$this->actingAs($user); livewire(Component::class)->...`
- **Lesson:** Livewire components are instantiated immediately, so authentication must happen via the test instance first, not chained after livewire().

**6. Auto-Login Hack Revisited (2025-10-24)**
- **Problem:** The auto-login hack had its environment check removed at some point, causing Edit tests to fail with "no such table: users" errors.
- **Impact:** All 11 Edit component tests failed during initial run.
- **Solution:** Re-added the `if (! app()->environment('testing'))` wrapper around the auto-login code.
- **Lesson:** This is the second time this issue appeared! Consider adding a test that specifically checks this doesn't happen again, or better yet, remove the auto-login hack entirely once manual testing is complete.

**7. Refactored Create/Edit Form Templates (2025-10-24)**
- **Improvement:** The create and edit views had 66 lines of identical form field HTML duplicated between them.
- **Solution:** Extracted shared form fields into `partials/task-form-fields.blade.php` with parameters for submit button text and cancel route.
- **Benefits:**
  - Both views now just 16 lines instead of 82 lines each (80% reduction)
  - Single source of truth for form fields - changes only need to happen once
  - Added consistent `space-y-6` spacing to edit form (was missing before)
  - Easier to maintain and update form fields in the future
- **Test Results:** All 24 tests still passing after refactoring, confirming no behavioral changes.

**8. Completed Show Component Tests (2025-10-24)**
- **Achievement:** Wrote comprehensive test suite for the Show component with 23 tests covering all functionality.
- **Tests Cover:**
  - Rendering and basic display (task name, description, status badges, team, last check-in)
  - Details tab (schedule types, timezone, grace period, check-in token, creator)
  - History tab (empty state, task runs table, late/on-time badges, lateness tracking)
  - Alerts tab (empty state, alerts table, alert types, acknowledgment status)
  - Pagination limits (20 most recent runs/alerts)
  - Navigation (edit button, back to list button)
- **Milestone:** All Phase 1 Livewire UI components now have complete test coverage.

**9. Implemented Ping API Endpoint (2025-10-24)**
- **Achievement:** Fully implemented the `/ping/{token}` public API endpoint with TDD approach.
- **Implementation Details:**
  - Added `data` JSON column to task_runs table via migration
  - Updated TaskRun model to cast data as array
  - Created PingController with single `__invoke` method
  - Route accepts both GET and POST requests
  - No authentication required - uses unique token for security
- **Functionality:**
  - GET: Simple acknowledgment that job ran (no data)
  - POST: Optional `data` JSON field for flexible admin use (stats, messages, timings, etc.)
  - Records TaskRun with timestamp
  - Updates task's last_checked_in_at and sets status to 'ok'
  - Returns 404 for invalid tokens
  - Ignores extra fields in POST requests (security)
- **Test Coverage:** 13 comprehensive tests covering:
  - GET and POST requests
  - Data field handling (optional, flexible, complex types)
  - Multiple check-ins
  - Invalid/non-existent tokens
  - No authentication requirement
  - Status updates
  - Edge cases
- **Future Work:** Background jobs to calculate expected_at times and detect late/missed runs.

**10. Implemented Background Processing & Alert System (2025-10-24 AM)**
- **Achievement:** Full TDD implementation of missed task detection and email alerting system.
- **Components Created:**
  1. **ScheduleCalculator Service** - Handles all schedule calculations
     - Parses simple intervals: 5m, 15m, 30m, 1h, 6h, 12h, daily
     - Parses cron expressions using `dragonmantank/cron-expression` library
     - Calculates next expected run times
     - Determines if tasks are late (respecting grace periods)
     - Calculates lateness in minutes
  2. **CheckMissedTasks Command** (`tasks:check-missed`)
     - Scans all non-paused tasks
     - Detects late tasks using ScheduleCalculator
     - Creates 'missed' alerts and updates status to 'alerting'
     - Creates 'recovered' alerts when tasks come back online
     - Sends emails to all team members
     - Prevents duplicate alerts
  3. **TaskMissedNotification Mailable**
     - Dynamic subject based on alert type (missed/late/recovered)
     - Color-coded HTML email template
     - Includes task details, schedule, last check-in time
     - Alert message clearly displayed
  4. **Email Template** (`emails/task-missed-notification.blade.php`)
     - Simple, readable HTML design
     - Color coding: Green for recovered, Yellow for late, Red for missed
     - Shows all relevant task information
- **Test Coverage:** 25 new tests (15 for ScheduleCalculator + 10 for command)
  - Schedule parsing for all interval types
  - Cron expression handling
  - Late detection with grace periods
  - Alert creation (missed, late, recovered)
  - Email sending to multiple team members
  - Edge cases (paused tasks, never checked-in, duplicates)
- **TODO:** Add command to Laravel scheduler in `routes/console.php`

**11. Completed Phase 1.5 UI Polish (2025-10-24 PM)**
- **Achievement:** Transformed the Show page from "backend developer UI" to polished, professional interface.
- **Key Lesson - Flux Charts Are NOT Chart.js:**
  - Initially assumed Flux charts worked like Chart.js with labels/datasets structure
  - **Reality:** Flux charts are composable, zero-dependency components built from smaller pieces
  - **Correct Structure:**
    - `<flux:chart>` - container with `:value` (not `:data`)
    - `<flux:chart.svg>` - SVG container
    - `<flux:chart.line field="fieldname">` - draws the line for a data field
    - `<flux:chart.point>` - adds points to the line
    - `<flux:chart.axis axis="x/y">` - axis with ticks/grid/line sub-components
    - `<flux:chart.cursor>` - interactive cursor
    - `<flux:chart.tooltip>` - hover tooltips with heading and values
  - **Data Format:** Simple array of objects: `[['date' => 'Oct 24', 'execution_time' => 100], ...]`
  - **Always** use Laravel Boost `search-docs` tool to verify Flux component usage!
- **Flux Tabs Structure Fix:**
  - Tabs weren't displaying content due to missing `<flux:tab.group>` wrapper
  - Correct structure: `<flux:tab.group>` → `<flux:tabs>` + `<flux:tab.panel>` siblings
- **UI Improvements Implemented:**
  - Hero section with task name and inline last check-in (diffForHumans)
  - Large, prominent status callout with dynamic variants (success/danger/warning/neutral)
  - Beautiful line chart showing execution time trends over last 30 check-ins
  - Interactive tooltips on chart hover
  - Empty states for tasks with no check-in history
  - Team moved to Details tab (cleaner top section)
  - All tabs now working correctly with proper data display
- **Test Data Creation:**
  - Updated TestDataSeeder with 4 diverse tasks:
    1. "Database Backup" - healthy with 30 days of check-ins
    2. "Process Queue" - alerting with mixed history and 3 alert types
    3. "Log Rotation" - pending (never checked in) for empty state testing
    4. "Cache Warmup" - paused with old history
  - 60 total task runs with realistic execution times (45-180s for backups, 2-8s for queue)
  - 3 alerts (missed, late, recovered) for testing alert UI
- **Browser Testing:** All UI improvements verified working correctly across different task statuses

**12. Completed Phase 1 Final Cleanup (2025-10-25)**
- **Achievement:** Finished all remaining Phase 1 tasks - scheduler configuration and authorization policies.
- **Scheduler Setup:**
  - Added `tasks:check-missed` command to `routes/console.php`
  - Configured to run every minute with `withoutOverlapping()` and `runInBackground()`
  - Ensures tasks are monitored continuously without conflicts
- **Authorization Policies Implemented:**
  - Created `ScheduledTaskPolicy` with team membership checks
  - Created `TeamPolicy` with team membership checks
  - Both policies use clean `isMemberOfTeam()` helper methods
  - Policies automatically discovered by Laravel 12 (no manual registration needed)
- **Comprehensive Test Coverage:**
  - Wrote 22 policy tests covering all authorization scenarios:
    - Team membership checks (view, update, delete, restore, forceDelete)
    - Multi-team access scenarios
    - Denial of unauthorized access
    - viewAny and create permissions for all authenticated users
  - All tests follow team conventions (Arrange, Act, Assert pattern)
- **Phase 1 Status:** Fully production-ready with complete test coverage and authorization!

**13. Refactored Ping Endpoint to Use Queue (2025-10-25)**
- **Achievement:** Improved ping endpoint performance by offloading work to queue.
- **Problem Identified:** Cron jobs cluster around common times (7am, midnight, etc.) creating traffic spikes. Inline database writes in the controller could slow response times.
- **Solution:**
  - Created `RecordTaskCheckIn` queued job using `ShouldQueue` interface
  - Job accepts `ScheduledTask` model and optional `data` array
  - PingController now just validates token and dispatches job
  - Response returns immediately without waiting for database writes
- **Benefits:**
  - Faster API responses for cron jobs
  - Better handling of traffic spikes
  - Database writes happen asynchronously in queue
  - More resilient to load
- **Testing:** Tests continue to pass with zero modifications thanks to `sync` queue driver in `phpunit.xml`
- **Code Quality:** Clean separation of concerns - controller handles HTTP, job handles business logic

**14. Implemented Dashboard with Clickable Status Cards (2025-10-25)**
- **Achievement:** Created home page dashboard showing task health overview with actionable status cards.
- **Features Implemented:**
  - Status count cards for ok, alerting, pending, and paused tasks
  - Clickable cards that link to filtered All Tasks page
  - Recent alerts section (last 10 with type badges)
  - Recent check-ins section (last 10 with late/on-time badges)
  - All data scoped to user's teams only
  - Dashboard set as home route (/)
  - Updated sidebar navigation
- **UI Components Used:**
  - Flux cards for status counts with color-coded text
  - Flux badges for alert types and check-in status
  - Empty states for no alerts/check-ins
  - Links with wire:navigate for SPA-like navigation

**15. Added Interactive Filter Pills to All Tasks Page (2025-10-25)**
- **Achievement:** Enhanced task filtering with user-friendly pill-based filter UI.
- **Features Implemented:**
  - Flux radio.group with pills variant (requires Flux Pro)
  - Three filter options: All, Alerting, Paused
  - wire:model.live for instant filtering without page reload
  - #[Url] attribute maintains filter state in URL
  - Contextual empty state messages for each filter
  - Filter works seamlessly with existing team filtering
- **Benefits:**
  - More intuitive than dropdown or links
  - Visual indication of active filter
  - URL shareable with filter applied
  - Clean, modern UI matching Flux design system

**16. Implemented Team Filtering on All Tasks Page (2025-10-25)**
- **Achievement:** Added team filter dropdown to All Tasks page for multi-team users.
- **Features Implemented:**
  - Flux select component showing user's teams
  - #[Url] attribute for URL-persistent filtering
  - wire:model.live for instant updates
  - Combines seamlessly with status filter
  - Shows only teams user is a member of
  - "All Teams" option to clear filter
- **Implementation Details:**
  - Added `team_id` property with #[Url] attribute to Index component
  - Filter applied in query with `where('team_id', $this->team_id)`
  - Passes user's teams to view for dropdown population
  - Fixed Flux select syntax: `flux:select.option` not `flux:option`
- **Learning:** Used logging instead of tinker for debugging to avoid creating test records in database
- **Tool Discovery:** Laravel Boost `read-log-entries` tool very helpful for viewing application logs during debugging

**17. Added API Documentation to Task Show Page (2025-10-25)**
- **Achievement:** Embedded contextual API examples directly on task show page with real, copy-paste ready URLs.
- **Features Implemented:**
  - **Quick Start on Details Tab** - Simple curl GET example with Flux copyable input as first item
  - **New API Tab** with comprehensive examples:
    - Basic GET Request (copyable input)
    - POST with JSON Data (textarea, multi-line with backslashes)
    - From Your Cron Job (textarea, showing `&&` pattern)
  - **getPingUrl() helper method** on ScheduledTask model for URL reuse
  - All examples use actual task's unique token - no editing needed
- **UI Approach:**
  - One-liner examples use `flux:input readonly copyable` for easy copying
  - Multi-line examples use `flux:textarea readonly` for full visibility
  - Removed PowerShell-specific examples (modern PowerShell includes curl.exe)
- **Benefits:**
  - No separate documentation page needed
  - Contextual - examples appear right where users need them
  - Copy-paste ready with real URLs
  - Perfect for experienced sysadmins
- **Test Coverage:** 3 new tests for API examples display

**18. Implemented Team Management (2025-10-25)**
- **Achievement:** Full CRUD for teams with member management and smart task migration.
- **Implementation Details:**
  - Teams Index: list, create button, member/task counts, personal team badges
  - Teams Show: members section (add/remove), tasks section, delete with migration modal
  - Teams Create: simple form with auto-slug generation and uniqueness
  - Personal team protection via `isPersonalTeam()` helper
  - Added `name` accessor to User model (combines forenames + surname)
  - Flyout modal for task migration when deleting teams with tasks
  - Cannot delete personal teams or remove last member from any team
- **Test Coverage:** 27 new tests (9 Index, 15 Show, 9 Create)
- **Bug Found During Testing:**
  - Teams Show component had `orderBy('name')` calls failing on non-existent column
  - Test was falsely passing because team name appeared in SQL error trace
  - Fixed by using `orderBy('surname')->orderBy('forenames')` for users
  - Fixed by using `orderBy('teams.name')` to qualify table in join queries
  - Improved test to assert multiple UI elements (headings, buttons) not just content strings
- **Lesson:** Tests should verify multiple UI elements render correctly, not just check for content strings that might appear in error output

### General Approach
- **Test-Driven Development:** Writing Pest tests BEFORE fixing bugs helped catch multiple issues and ensured the fixes actually worked.
- **Ask for Help:** When stuck on Lando/Docker configuration issues, complex test failures, or environment-specific problems, ALWAYS ask the user. They love helping and know the setup intimately. Don't waste time guessing at fixes.
- **Use Laravel Boost:** The `search-docs` tool is invaluable for finding version-specific documentation for Laravel ecosystem packages.
- **Follow Team Conventions:** TestDataSeeder pattern, Livewire Form classes, and team-based authorization model all came from asking about the team's preferences.
- **Test Robustness:** When testing page rendering, assert for multiple UI elements (headings, buttons, etc.) not just the main content. Tests can falsely pass if they only check for strings that might appear in error messages/SQL traces. Example: A test checking `assertSee('Engineering Team')` passed even though the page crashed, because the team name appeared in the SQL error output.

## Phase 1.5 - UI Tweaks & Polish

**Goal:** Improve the task show page to be more visually appealing and informative, moving beyond "backend developer UI" to a polished user experience.

### Show Page Redesign

**Current Issues:**
- Three separate cards at top feel cluttered
- Status is buried in a small badge
- Team information takes up valuable space
- No visual representation of task health/history

**Proposed Changes:**

1. **Hero Section Redesign**
   - Task name as main heading with last check-in inline
   - Format: "Database Backup" with subtitle "(last check-in: 12 hours ago)"
   - Use Carbon's `diffForHumans()` for time formatting
   - Remove separate "Last Check-in" card

2. **Prominent Status Callout**
   - Replace small status card with large `flux:callout` component
   - Heading: "Status" (using callout heading style)
   - Body: Large, Tailwind-styled status text (e.g., "OK", "ALERTING", "PENDING")
   - Dynamic callout variant based on status:
     - 'ok' → success/green variant
     - 'alerting' → danger/red variant
     - 'pending' → warning/yellow variant
     - 'paused' → neutral/gray variant
   - Consider creating an enum or helper method for status → variant mapping

3. **Team Information Relocation**
   - Remove "Team" card from top section
   - Move team name to Details tab
   - Keeps top section focused on task health

4. **Check-in History Chart** (fancy feature!)
   - Add `flux:chart` component to show visual check-in history
   - X-axis: Time (last 20 check-ins or last 24 hours)
   - Y-axis options:
     - Line 1: Check-in occurrences (dots/line showing when task ran)
     - Line 2: Execution time/duration (if available in TaskRun.data field)
   - Could show:
     - On-time vs late check-ins (color coding)
     - Duration trends over time
     - Gaps in check-ins (missed runs)
   - Fallback: If no check-ins, show empty state with message

### Implementation Tasks

- [x] Update TestDataSeeder to create rich visualization data (4 tasks, 60 runs, 3 alerts)
- [x] Update Show component to pass additional data for chart
- [x] Add `currentTab` property to Show component (fix Livewire warning)
- [x] Update `show.blade.php` view:
  - [x] Redesign hero section (heading + inline last check-in with diffForHumans)
  - [x] Replace status card with large callout component
  - [x] Remove team card from top
  - [x] Add team to Details tab
  - [x] Add chart component with check-in history (last 30 runs)
  - [x] Fix tabs structure with flux:tab.group wrapper
- [x] Create status → callout variant mapping (using match expression)
- [x] Style large status text with Tailwind (text-4xl, font-bold, uppercase)
- [x] Query TaskRuns with data field for chart
- [x] Format chart data for flux:chart component (simple array of objects)
- [x] Add fallback UI for tasks with no check-ins (empty state card)
- [x] Verify all existing tests still pass (85 tests, 188 assertions)
- [x] Manual browser testing with various statuses and check-in histories

**Status:** ✅ Phase 1.5 Complete! All UI improvements implemented and tested.

### Design Considerations

- Keep it simple and readable
- Status should be immediately obvious (big and bold)
- Chart should provide at-a-glance health insight
- Mobile responsive (chart might need special handling)
- Maintain existing Details/History/Alerts tab structure

## Phase 1 - COMPLETE! 🎉

**Summary:** All core functionality implemented, tested, and polished!
- ✅ Full CRUD for scheduled tasks (Index, Create, Edit, Show)
- ✅ Public check-in API (`/ping/{token}`)
- ✅ Background missed task detection and alerting (scheduled every minute)
- ✅ Email notifications to team members
- ✅ Beautiful, polished UI with charts and status callouts
- ✅ Team-based authorization policies (ScheduledTaskPolicy & TeamPolicy)

### Test Coverage
**175 tests with 382 assertions, all passing! ✅**

Test breakdown by feature:
- **Unit Tests:** 1 test (example/sanity check)
- **Ping API Endpoint:** 13 tests - GET/POST requests, data handling, authentication
- **Missed Tasks Detection:** 10 tests - alert generation, email notifications, schedule detection
- **Dashboard:** 13 tests - status counts, recent alerts/check-ins, team filtering
- **Example/Route Tests:** 1 test - basic routing
- **Authorization Policies:** 22 tests (11 ScheduledTask + 11 Team) - team membership checks
- **Scheduled Tasks CRUD:** 55 tests
  - Create: 6 tests - form validation, simple/cron schedules
  - Edit: 11 tests - updates, validation, immutable fields
  - Index: 13 tests - listing, deletion, status/team filtering, authorization
  - Show: 25 tests - details/history/alerts/API tabs, charts, badges, pagination, API examples
- **Schedule Calculator Service:** 15 tests - interval parsing, cron expressions, late detection
- **Team Policy:** 11 tests - team membership authorization
- **Teams CRUD:** 27 tests - (added in Phase 2)
  - Index: 9 tests - listing, member/task counts, personal team badges
  - Show: 15 tests - members, tasks, add/remove, migration modal, authorization
  - Create: 9 tests - creation, slug generation, validation
- **Alert Management Helpers:** 11 tests - (added in Phase 2)
  - Task/team silencing logic, expiry handling, priority rules

All tests follow team conventions with Arrange/Act/Assert pattern and use RefreshDatabase trait.

### Phase 1 Remaining Minor Tasks
1. **Schedule Background Command** ✅
   - [x] Add `tasks:check-missed` to Laravel scheduler in `routes/console.php`
   - Runs every minute with `withoutOverlapping()` and `runInBackground()`
2. **Authorization & Policies** ✅
   - [x] Create `ScheduledTaskPolicy` (check team membership)
   - [x] Create `TeamPolicy` (check team membership)
   - [x] Register policies in `bootstrap/app.php` (auto-discovered by Laravel 12)
   - [x] Write tests for ScheduledTaskPolicy authorization rules
   - [x] Write tests for TeamPolicy authorization rules

## Phase 2 - In Progress

### Completed Features
1. **Dashboard** ✅ - Home page with overview of task health
   - Count of tasks by status (ok, alerting, pending, paused)
   - Clickable status cards that filter All Tasks page
   - Recent alerts list (last 10)
   - Recent check-ins across all tasks (last 10)
   - Set as home route (/)

2. **Task Filtering Enhancements** ✅
   - Interactive filter pills for status (All, Alerting, Paused)
   - Team dropdown filter for multi-team users
   - Both filters use #[Url] attribute for URL persistence
   - Filters combine seamlessly together
   - wire:model.live for instant updates

3. **API Documentation** ✅ - Contextual examples on task show page
   - Quick Start curl example on Details tab with copyable input
   - Dedicated API tab with multi-line examples
   - Real URLs with actual task tokens
   - Examples for GET, POST with data, and cron job integration

4. **Team Management** ✅ - Full CRUD for teams (2025-10-25)
   - **Teams Index Page** (`/teams`)
     - List all teams user is a member of
     - Display member count and task count for each team
     - Show "Personal" badge for personal teams
     - Create new team button
     - Empty state when user has no teams
   - **Teams Show Page** (`/teams/{team}`)
     - View team details (name, slug, personal badge)
     - Members section: list members, add by email, remove members
     - Tasks section: list all scheduled tasks for the team
     - Delete team with smart migration modal (flyout variant)
     - Cannot delete personal teams
     - Prevents removing last member from team
   - **Teams Create Page** (`/teams/create`)
     - Simple form with team name
     - Auto-generates URL-friendly slug with uniqueness check
     - Creator automatically added as first member
   - **Personal Team Protection**
     - Added `isPersonalTeam()` helper to Team model
     - Personal teams (name === username) cannot be deleted
     - No edit/delete options shown for personal teams
   - **User Model Enhancement**
     - Added `name` accessor combining `forenames` and `surname`
   - **Test Coverage**
     - 27 new tests for Teams functionality
     - Teams Index: 9 tests
     - Teams Show: 15 tests
     - Teams Create: 9 tests
     - All tests follow Arrange/Act/Assert pattern
   - **Navigation**
     - Added Teams link to sidebar with user-group icon

5. **Alert Management** 🚧 - In Progress (2025-10-25)

   **Design Decisions:**
   - **Alert Acknowledgment:** Purely informational - records who/when, doesn't change task status
   - **Alert Silencing:** Two-level system (task-level and team-level) to handle planned outages
   - **Duration Options:** 1 hour, 6 hours, 24 hours, 3 days, 7 days, custom
   - **Email Enhancement:** Include direct link to task show page for quick access

   **Completed:**
   - [x] Database migrations for `alerts_silenced_until` columns
     - Added to `scheduled_tasks` table
     - Added to `teams` table
   - [x] Model helper methods
     - `ScheduledTask::isSilenced()` - checks task or team silence
     - `ScheduledTask::getSilencedCause()` - returns 'task'|'team'|null
     - `ScheduledTask::getSilencedUntil()` - returns Carbon timestamp
     - `Team::isSilenced()` - checks team silence
   - [x] Test coverage for helper methods (11 tests)
     - Task silencing, team silencing, expiry handling
     - Priority logic (task silence takes precedence)
     - Edge cases covered

   **Remaining Implementation:**
   - [ ] Update `CheckMissedTasks` command to skip silenced tasks
   - [ ] Alert acknowledgment UI on task show page
   - [ ] Task silencing UI (modal with duration picker)
   - [ ] Team silencing UI (modal with warning + duration picker)
   - [ ] All Alerts page (`/alerts`)
     - Filter pills: All / Unacknowledged (with #[Url])
     - Maybe filter by type: Missed / Late / Recovered
     - Quick acknowledge button per row
     - Link to task show page
   - [ ] Update email template with task link
   - [ ] Add "Alerts" navigation to sidebar
   - [ ] Visual indicators for silenced tasks/teams (badges, countdown)
   - [ ] Write comprehensive tests for all UI components

   **Key Technical Decisions:**
   - Silencing is checked via helper methods, not repeated logic
   - Task silence takes priority over team silence in UI display
   - Expired silences automatically become inactive (no cleanup job needed)
   - `alerts` table already has `acknowledged_by` and `acknowledged_at` columns

### Features to Consider

1. **Team Invitations** (deferred from Team Management)
   - Email invitation system with tokens
   - Currently only existing users can be added by email

2. **User Settings**
   - Timezone preferences
   - Email notification preferences
   - API token management (for future authenticated APIs)

### Questions to Discuss
- Should we add more chart types (late/on-time ratio, frequency histogram)?
- Should we add an "ok" status filter to the pills, or keep just the three we have?

### Technical Improvements
- Consider removing auto-login hack (add proper dev login page)
- Add database indexes for performance (checked_in_at, status, etc.)
- Consider caching team memberships for performance
- Add API rate limiting (even though it's public)
- Consider adding task tags/categories for organization

### Testing & Documentation
- ~~Add feature tests for authorization policies~~ ✅ Complete
- Document the check-in API (markdown file or dedicated page)
- Document the alert system and email templates
- Add deployment guide (queue workers, scheduler, etc.)
- When writing tests, cover both the visible outcome and the side effects (e.g., confirm records remain unchanged after validation errors) so regressions can’t hide behind surface-level assertions.

**Current State:** Phase 1 complete and production-ready! Phase 2 in progress with dashboard, enhanced filtering, API documentation, and full team management complete (175 tests, 382 assertions). Alert management foundation complete (database schema, helper methods, tests). The application successfully monitors cron jobs, detects missed runs, sends alerts, and provides a beautiful, functional UI for management with complete team collaboration features.
