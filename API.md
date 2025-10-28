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
