# Import/Export Feature

## Overview

A JSON-based import/export system that allows teams to migrate their configuration and data between environments (e.g., test → production). This is particularly useful for moving validated setups without manual re-entry.

## Design Principles

### Matching Strategy
- **Users**: Match by `username` - updates existing users or creates new ones
- **Teams**: Match by `name` (create if doesn't exist)
- **Scheduled Tasks**: Match by `name` within the same team context

### What Gets Exported
- **All users** with their account details (username, email, names, staff/admin flags)
- **All teams** (both personal and shared) in the entire system
- **All scheduled tasks** with their configuration
- **All team memberships**
- Task metadata (descriptions, schedules, grace periods, etc.)

### What Doesn't Get Exported
- User passwords (random passwords set on import, users will use SSO)
- Task run history (fresh start in new environment)
- Alerts (fresh start in new environment)
- Audit logs (environment-specific)
- Check-in tokens (regenerated on import)

## Data Structure

The export JSON will have this structure:

```json
{
  "version": "1.0",
  "exported_at": "2025-10-31T09:23:00Z",
  "exported_by": "billy",
  "data": {
    "users": [
      {
        "username": "billy",
        "email": "billy@example.com",
        "forenames": "Billy",
        "surname": "Smith",
        "is_staff": true,
        "is_admin": false
      }
    ],
    "teams": [
      {
        "name": "billy",
        "slug": "billy",
        "is_personal": true,
        "owner_username": "billy",
        "members": ["billy"]
      },
      {
        "name": "Marketing Team",
        "slug": "marketing-team",
        "is_personal": false,
        "owner_username": null,
        "members": ["billy", "sarah", "john"]
      }
    ],
    "scheduled_tasks": [
      {
        "team_name": "billy",
        "name": "Database Backup",
        "description": "Nightly backup of production database",
        "schedule_type": "cron",
        "schedule_value": "0 3 * * *",
        "timezone": "America/New_York",
        "grace_period_minutes": 15,
        "status": "ok",
        "created_by_username": "billy"
      }
    ]
  }
}
```

## Import Logic

### User Mapping
1. Look up users by `username` field
2. If user doesn't exist, skip that team member (log warning)
3. Continue with available users

### Team Processing
1. For each team in import:
   - If `is_personal: true`:
     - Find or create personal team for `owner_username`
     - Use existing team if already exists
   - If `is_personal: false`:
     - Find team by `name` or create new one
     - Generate unique slug if needed
2. Add team members (skip users that don't exist)

### Task Processing
1. For each scheduled task:
   - Find team by `team_name`
   - Find task by `name` within that team
   - If exists: **Update** configuration
   - If doesn't exist: **Create** new task
   - Generate new `unique_check_in_token` (UUID)
   - Set `last_checked_in_at` and `next_expected_at` to null
   - Set status to `pending` (tasks need to check in)
   - Map `created_by_username` to user (or null if not found)

### Conflict Resolution
- **Teams**: Match by name, update membership
- **Tasks**: Match by (team_name + task_name), update configuration
- **Tokens**: Always regenerate to avoid collisions
- **Slugs**: Append number suffix if slug collision (-2, -3, etc.)

## UI Implementation

### Export Page (`/export`)
- Single page with large "Export All Data" button
- Shows preview of what will be exported (total team count, total task count across entire system)
- Downloads JSON file with filename: `cronmon-export-{date}.json`
- Requires authentication
- **Exports ALL data in the system** (not limited to current user's teams)

### Import Page (`/import`)
- File upload form (accepts `.json` files)
- Validation:
  - Checks JSON structure
  - Validates version compatibility
  - Shows preview of what will be imported
- Preview screen shows:
  - Teams to be created/updated
  - Tasks to be created/updated
  - Any warnings (missing users, conflicts, etc.)
- "Confirm Import" button executes the import
- Shows success/error messages with details
- Requires authentication
- Available to all authenticated users

### Navigation
- Add "Import/Export" link to main navigation
- Landing page with two options: Export or Import

## Technical Implementation

### Components
1. **Livewire Component**: `App\Livewire\ImportExport\Index`
   - Landing page with export/import options

2. **Livewire Component**: `App\Livewire\ImportExport\Export`
   - Export interface and download

3. **Livewire Component**: `App\Livewire\ImportExport\Import`
   - Upload, preview, and confirm import

### Service Classes
1. **`App\Services\ExportService`**
   - `export(): array` - Generates export data structure
   - Queries teams and tasks accessible to current user
   - Transforms to portable format

2. **`App\Services\ImportService`**
   - `validate(array $data): ValidationResult` - Checks structure and compatibility
   - `preview(array $data): ImportPreview` - Shows what will happen
   - `execute(array $data): ImportResult` - Performs the import
   - Uses database transactions
   - Handles user/team/task mapping

### Routes
```php
Route::middleware('auth')->group(function () {
    Route::get('/import-export', \App\Livewire\ImportExport\Index::class)
        ->name('import-export.index');
    Route::get('/import-export/export', \App\Livewire\ImportExport\Export::class)
        ->name('import-export.export');
    Route::get('/import-export/import', \App\Livewire\ImportExport\Import::class)
        ->name('import-export.import');
});
```

### Authorization
- Any authenticated user can export ALL data in the system
- Any authenticated user can import data, creating teams they'll be part of
- No special permissions needed (all authenticated users can access the feature)

## Edge Cases & Considerations

1. **Large Datasets**: 
   - Consider chunking for very large exports
   - Add progress indicator for imports
   - May need to increase PHP memory/execution limits

2. **Circular References**: 
   - Not applicable (no circular relationships in our model)

3. **Validation**:
   - Strict schema validation on import
   - Reject invalid/malformed JSON immediately
   - Provide clear error messages

4. **Idempotency**:
   - Running same import twice should be safe
   - Updates existing records rather than creating duplicates

5. **Token Regeneration**:
   - Old check-in URLs won't work after import
   - This is intentional (prevents cross-environment confusion)
   - Users need to update their cron jobs with new tokens

6. **Personal Teams**:
   - Always preserve personal team relationship
   - Don't accidentally convert personal → shared or vice versa
   - Ensure `user_id` is set correctly on import

## Testing Strategy

### Unit Tests
- `ExportService` generates correct structure
- `ImportService` validates data correctly
- User/team/task mapping logic

### Feature Tests
- Complete export/import round-trip
- Handle missing users gracefully
- Handle team conflicts (same name)
- Handle task conflicts (same name in team)
- Verify token regeneration
- Test with personal and shared teams

### Test Scenarios
1. Export from user A, import as user B (different users exist)
2. Export with personal team, import where user doesn't exist
3. Export shared team, import where some members don't exist
4. Import duplicate (run same import twice) - should be idempotent
5. Import with conflicting team names
6. Import with conflicting task names within same team
7. Import invalid JSON structure
8. Import with unsupported version number

## Current Status (2025-10-31)

### ✅ Completed
- Export service now exports **ALL users, teams, and tasks** in the system
- Import service handles user creation/updates with random passwords (SSO login)
- UI updated to show users are included in export
- Documentation updated to reflect user export/import
- Export/Import components updated with user counts in preview
- Import validation requires `users` array in data

### ⚠️ Needs Fixing
- **Tests are broken**: Need to add `users` array to all test export data in `tests/Feature/ImportExport/ImportTest.php`
  - Lines that need fixing: ~131, 166, 210, 245, 287, 334
  - Each export data structure needs a `users` array added
  - Format example:
    ```php
    'users' => [
        [
            'username' => 'testuser',
            'email' => $user->email,
            'forenames' => $user->forenames,
            'surname' => $user->surname,
            'is_staff' => $user->is_staff,
            'is_admin' => $user->is_admin,
        ],
    ],
    ```
- Currently 5-6 tests failing because of missing users array in test data

### 📝 TODO After Tests Fixed
- Run full test suite to ensure everything passes
- Run `vendor/bin/pint` to format code
- Manual testing with real data
- Update PROJECT_PLAN.md

## Progress Checklist

### Backend - Service Layer
- [x] Create `ExportService` class with `export()` method
- [x] Create `ImportService` class with `validate()` method
- [x] Implement `ImportService::preview()` method
- [x] Implement `ImportService::execute()` method with transaction
- [x] Add user mapping logic (by username)
- [x] Add team mapping logic (by name, handle personal teams)
- [x] Add task mapping logic (by team + name)
- [x] Implement token regeneration for imported tasks
- [x] Add slug conflict resolution (append suffix)
- [ ] Write unit tests for `ExportService`
- [ ] Write unit tests for `ImportService`

### Backend - Routes & Controllers
- [x] Add routes for import/export pages
- [x] Ensure routes are in auth middleware group

### Frontend - Livewire Components
- [x] Create `ImportExport\Index` component (landing page)
- [x] Create `ImportExport\Export` component
- [x] Add export preview (team/task counts)
- [x] Add download functionality (JSON file response)
- [x] Create `ImportExport\Import` component
- [x] Add file upload form with validation
- [x] Add import preview display
- [x] Add warning messages display (missing users, etc.)
- [x] Add confirm import button
- [x] Add success/error feedback messages

### Frontend - Views & UI
- [x] Create Flux-styled landing page view
- [x] Create export page view with Flux components
- [x] Create import page view with Flux components
- [x] Add "Import/Export" link to main navigation
- [x] Style file upload area
- [x] Style preview panels
- [x] Add loading states for long operations

### Testing
- [x] Write feature test: full export/import round-trip
- [x] Write feature test: import with missing users
- [x] Write feature test: import with team name conflicts
- [x] Write feature test: import with task name conflicts
- [x] Write feature test: idempotent import (run twice)
- [x] Write feature test: personal team preservation
- [x] Write feature test: token regeneration verification
- [x] Write feature test: invalid JSON rejection
- [ ] Manual testing: export from test, import to dev
- [ ] Manual testing: large dataset (100+ tasks)

### Documentation
- [ ] Update PROJECT_PLAN.md with import/export feature
- [ ] Add usage instructions to this document
- [ ] Document token regeneration requirement for users
- [ ] Add troubleshooting section

## Future Enhancements (Out of Scope)

- Selective export (choose specific teams/tasks)
- Import dry-run mode
- Scheduled automatic exports (backup feature)
- Export task run history as optional
- Import from other cron monitoring services
- API endpoints for programmatic import/export
- Encryption for exported files (if they contain sensitive data)
