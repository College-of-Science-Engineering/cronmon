<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('validates import data structure', function () {
    $importService = new ImportService;

    $result = $importService->validate([
        'version' => '1.0',
        'data' => [
            'users' => [],
            'teams' => [],
            'scheduled_tasks' => [],
        ],
    ]);

    expect($result->valid)->toBeTrue()
        ->and($result->errors)->toBeEmpty();
});

it('rejects invalid version', function () {
    $importService = new ImportService;

    $result = $importService->validate([
        'version' => '2.0',
        'data' => [
            'users' => [],
            'teams' => [],
            'scheduled_tasks' => [],
        ],
    ]);

    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toContain('Unsupported version: 2.0');
});

it('rejects missing data field', function () {
    $importService = new ImportService;

    $result = $importService->validate([
        'version' => '1.0',
    ]);

    expect($result->valid)->toBeFalse()
        ->and($result->errors)->toContain('Missing data field');
});

it('can import a complete export round trip', function () {
    $user = User::factory()->create(['username' => 'testuser']);
    $team = Team::factory()->create(['name' => 'Test Team']);
    $user->teams()->attach($team);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Original Task',
        'description' => 'Test task',
        'schedule_type' => 'cron',
        'schedule_value' => '0 0 * * *',
        'created_by' => $user->id,
    ]);

    $originalToken = $task->unique_check_in_token;

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'testuser',
        'data' => [
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
            'teams' => [
                [
                    'name' => 'Test Team',
                    'slug' => 'test-team',
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => ['testuser'],
                ],
            ],
            'scheduled_tasks' => [
                [
                    'team_name' => 'Test Team',
                    'name' => 'Original Task',
                    'description' => 'Test task',
                    'schedule_type' => 'cron',
                    'schedule_value' => '0 0 * * *',
                    'timezone' => 'UTC',
                    'grace_period_minutes' => 10,
                    'status' => 'ok',
                    'created_by_username' => 'testuser',
                ],
            ],
        ],
    ];

    $importService = new ImportService;
    $result = $importService->execute($exportData);

    expect($result->success)->toBeTrue()
        ->and($result->teamsUpdated)->toBe(1)
        ->and($result->tasksUpdated)->toBe(1);

    $task->refresh();
    expect($task->name)->toBe('Original Task')
        ->and($task->unique_check_in_token)->not->toBe($originalToken);
});

it('creates new team when importing', function () {
    $user = User::factory()->create(['username' => 'testuser']);

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'testuser',
        'data' => [
            'teams' => [
                [
                    'name' => 'New Team',
                    'slug' => 'new-team',
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => ['testuser'],
                ],
            ],
            'scheduled_tasks' => [],
        ],
    ];

    $importService = new ImportService;
    $result = $importService->execute($exportData);

    expect($result->success)->toBeTrue()
        ->and($result->teamsCreated)->toBe(1);

    $team = Team::where('name', 'New Team')->first();
    expect($team)->not->toBeNull()
        ->and($team->users)->toHaveCount(1)
        ->and($team->users->first()->username)->toBe('testuser');
});

it('creates new task when importing', function () {
    $user = User::factory()->create(['username' => 'testuser']);
    $team = Team::factory()->create(['name' => 'Test Team']);
    $user->teams()->attach($team);

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'testuser',
        'data' => [
            'teams' => [
                [
                    'name' => 'Test Team',
                    'slug' => 'test-team',
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => ['testuser'],
                ],
            ],
            'scheduled_tasks' => [
                [
                    'team_name' => 'Test Team',
                    'name' => 'New Task',
                    'description' => 'Imported task',
                    'schedule_type' => 'simple',
                    'schedule_value' => '1h',
                    'timezone' => 'UTC',
                    'grace_period_minutes' => 5,
                    'status' => 'ok',
                    'created_by_username' => 'testuser',
                ],
            ],
        ],
    ];

    $importService = new ImportService;
    $result = $importService->execute($exportData);

    expect($result->success)->toBeTrue()
        ->and($result->tasksCreated)->toBe(1);

    $task = ScheduledTask::where('name', 'New Task')->first();
    expect($task)->not->toBeNull()
        ->and($task->description)->toBe('Imported task')
        ->and($task->status)->toBe('pending')
        ->and($task->unique_check_in_token)->not->toBeNull();
});

it('handles missing user gracefully', function () {
    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'unknown',
        'data' => [
            'teams' => [
                [
                    'name' => 'Test Team',
                    'slug' => 'test-team',
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => ['nonexistent'],
                ],
            ],
            'scheduled_tasks' => [],
        ],
    ];

    $importService = new ImportService;
    $preview = $importService->preview($exportData);

    expect($preview->warnings)->toContain("User 'nonexistent' not found for team 'Test Team' - will skip");
});

it('regenerates tokens on import', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    $originalTask = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Task',
    ]);
    $originalToken = $originalTask->unique_check_in_token;

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => $user->username,
        'data' => [
            'teams' => [
                [
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => [$user->username],
                ],
            ],
            'scheduled_tasks' => [
                [
                    'team_name' => $team->name,
                    'name' => 'Test Task',
                    'description' => null,
                    'schedule_type' => 'cron',
                    'schedule_value' => '0 0 * * *',
                    'timezone' => 'UTC',
                    'grace_period_minutes' => 10,
                    'status' => 'ok',
                    'created_by_username' => $user->username,
                ],
            ],
        ],
    ];

    $importService = new ImportService;
    $importService->execute($exportData);

    $originalTask->refresh();
    expect($originalTask->unique_check_in_token)->not->toBe($originalToken);
});

it('is idempotent running same import twice', function () {
    $user = User::factory()->create(['username' => 'testuser']);
    $team = Team::factory()->create(['name' => 'Test Team']);
    $user->teams()->attach($team);

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'testuser',
        'data' => [
            'teams' => [
                [
                    'name' => 'Test Team',
                    'slug' => 'test-team',
                    'is_personal' => false,
                    'owner_username' => null,
                    'members' => ['testuser'],
                ],
            ],
            'scheduled_tasks' => [
                [
                    'team_name' => 'Test Team',
                    'name' => 'Idempotent Task',
                    'description' => 'Test',
                    'schedule_type' => 'cron',
                    'schedule_value' => '0 0 * * *',
                    'timezone' => 'UTC',
                    'grace_period_minutes' => 10,
                    'status' => 'ok',
                    'created_by_username' => 'testuser',
                ],
            ],
        ],
    ];

    $importService = new ImportService;

    $result1 = $importService->execute($exportData);
    expect($result1->success)->toBeTrue()
        ->and($result1->tasksCreated)->toBe(1);

    $result2 = $importService->execute($exportData);
    expect($result2->success)->toBeTrue()
        ->and($result2->tasksCreated)->toBe(0)
        ->and($result2->tasksUpdated)->toBe(1);

    expect(ScheduledTask::where('name', 'Idempotent Task')->count())->toBe(1);
});

it('preserves personal team relationship', function () {
    $user = User::factory()->create(['username' => 'testuser']);

    $exportData = [
        'version' => '1.0',
        'exported_at' => now()->toIso8601String(),
        'exported_by' => 'testuser',
        'data' => [
            'teams' => [
                [
                    'name' => 'testuser',
                    'slug' => 'testuser',
                    'is_personal' => true,
                    'owner_username' => 'testuser',
                    'members' => ['testuser'],
                ],
            ],
            'scheduled_tasks' => [],
        ],
    ];

    $importService = new ImportService;
    $result = $importService->execute($exportData);

    expect($result->success)->toBeTrue();

    $personalTeam = Team::where('user_id', $user->id)->first();
    expect($personalTeam)->not->toBeNull()
        ->and($personalTeam->isPersonalTeam())->toBeTrue()
        ->and($personalTeam->user_id)->toBe($user->id);
});

it('can access import page when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('import-export.import'))
        ->assertSuccessful()
        ->assertSeeLivewire(\App\Livewire\ImportExport\Import::class);
});
