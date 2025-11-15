<?php

use App\Http\Controllers\Api\PingController;
use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Dashboard;
use App\Livewire\ImportExport\Export;
use App\Livewire\ImportExport\Import;
use App\Livewire\ImportExport\Index as ImportExportIndex;
use App\Livewire\ScheduledTasks\Index as ScheduledTasksIndex;
use App\Livewire\ScheduledTasks\Show;
use App\Livewire\Settings\Tokens as SettingsTokens;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// TEMPORARY: Auto-login as first user for development
// TODO: Remove this before production!
// if (! app()->environment('testing')) {
//    if (User::count() != 0) {
//        Auth::login(User::first());
//    }
// }
require __DIR__.'/sso-auth.php';

// Public API endpoint for check-ins (no authentication required)
Route::match(['get', 'post'], '/ping/{token}', PingController::class)->name('api.ping');

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/tasks', ScheduledTasksIndex::class)->name('tasks.index');
    Route::get('/tasks/{task}', Show::class)->name('tasks.show');

    Route::get('/audit-logs', AuditLogsIndex::class)->name('audit-logs.index');

    Route::get('/teams', \App\Livewire\Teams\Index::class)->name('teams.index');
    Route::get('/teams/create', \App\Livewire\Teams\Create::class)->name('teams.create');
    Route::get('/teams/{team}', \App\Livewire\Teams\Show::class)->name('teams.show');

    Route::get('/import-export', ImportExportIndex::class)->name('import-export.index');
    Route::get('/import-export/export', Export::class)->name('import-export.export');
    Route::get('/import-export/import', Import::class)->name('import-export.import');

    Route::get('/settings', SettingsTokens::class)->name('settings.tokens');
});
