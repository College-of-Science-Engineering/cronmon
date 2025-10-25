# Test Suite Review

## What’s Working
- `tests/Feature/DashboardTest.php:13-295` covers the major dashboard sections (status cards, alerts, check-ins, access control) and uses factories to build realistic data, which keeps the UI regression surface in check.
- `tests/Feature/ScheduledTaskPolicyTest.php:10-159` and `tests/Feature/TeamPolicyTest.php:9-123` explicitly exercise the policy gates so the membership rules can’t silently regress when team relationships change.
- Every feature test pulls in `RefreshDatabase`, so the suite stays isolated and deterministic even under heavy factory usage.

## Gaps & Risks
- Several dashboard assertions rely on bare `assertSee('2')`/`assertSee('1')` calls (`tests/Feature/DashboardTest.php:42-45`, `tests/Feature/DashboardTest.php:66`, `tests/Feature/DashboardTest.php:242`), which could pass because of unrelated numbers elsewhere in the layout; the intent (specific status counts or limited collections) isn’t actually guaranteed.
- The “limits check-ins to 10 most recent” scenario never proves that older entries are hidden (`tests/Feature/DashboardTest.php:222-243`); every row reuses the same task name, so the assertion will pass even if 15 rows render.
- Deletion and silencing safeguards on teams are implemented in the Livewire layer but have no coverage: preventing personal team deletion, forcing task migrations, and blocking removal of the final member (`app/Livewire/Teams/Show.php:31-99`) all depend on manual QA right now.
- Scheduled task CRUD flows have zero tests—no validation checks, no happy-path persistence, and no authorization guard on `mount()` (`app/Livewire/ScheduledTasks/{Create,Edit,Index,Show}.php:9-74` and `app/Livewire/Forms/ScheduledTaskForm.php:15-73`), so regressions in form rules or accidental data loss wouldn’t be caught.
- The public ping API only has a negative test (`tests/Feature/ExampleTest.php:4-9`); we never assert that a valid token dispatches `RecordTaskCheckIn` or that the job updates `TaskRun`/`last_checked_in_at` correctly (`app/Jobs/RecordTaskCheckIn.php:18-49`).
- Policy tests assert positive paths for `restore`/`forceDelete` but never the denials, so a future change that unintentionally opens those abilities to outsiders would slip through (`tests/Feature/ScheduledTaskPolicyTest.php:133-158`, `tests/Feature/TeamPolicyTest.php:105-123`).

## Recommendations
1. Tighten the dashboard specs by asserting on scoped DOM fragments (e.g. `assertSeeInOrder`, unique strings, or component-specific text) and by adding negative assertions for the trimmed collections.
2. Add Livewire feature tests for the Team and Scheduled Task screens that cover validation errors, authorization checks, and behavioural rules (duplicates, last-member protection, migration modal workflow).
3. Introduce API + job tests that simulate a successful ping, assert the job dispatch, and verify the downstream TaskRun/Status mutations, ideally faking the queue to keep the run fast.
4. Extend the policy suite with explicit denial cases for every ability and add coverage for business rules that sit outside the policies (personal team deletion, task migration requirements).
5. Map out remaining high-value journeys (creating/editing tasks, acknowledging alerts, silencing entities) and grow the feature suite so UI regressions surface in CI instead of production.

## Action Items
- [x] Harden dashboard assertions so count and limit checks target the intended elements (keep using Pest + `RefreshDatabase`).
- [x] Add Livewire feature coverage for teams (personal-team guard, task migration modal, member add/remove rules) in the existing Pest style.
- [x] Add Livewire feature coverage for scheduled task create/edit flows, including validation failures and successful persistence.
- [x] Create API/queue coverage for `/ping/{token}` that fakes the queue, asserts `RecordTaskCheckIn` dispatch, and confirms TaskRun/task status updates.
- [x] Move the ping 404 test out of `tests/Feature/ExampleTest.php:4-9` and into the new ping feature test file.
- [ ] Extend policy specs with explicit denials for restore/force delete (and any future abilities) so membership leaks are caught early.
- [ ] Mirror the existing Arrange/Act/Assert Pest conventions when adding new tests to avoid style drift.
