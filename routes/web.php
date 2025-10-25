<?php

use App\Http\Controllers\Api\PingController;
use App\Livewire\Dashboard;
use App\Livewire\ScheduledTasks\Create;
use App\Livewire\ScheduledTasks\Edit;
use App\Livewire\ScheduledTasks\Index;
use App\Livewire\ScheduledTasks\Show;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('auth.logout');

// Public API endpoint for check-ins (no authentication required)
Route::match(['get', 'post'], '/ping/{token}', PingController::class)->name('api.ping');

// TEMPORARY: Auto-login as first user for development
// TODO: Remove this before production!
// if (! app()->environment('testing')) {
//    if (User::count() != 0) {
//        Auth::login(User::first());
//    }
//}

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/tasks', Index::class)->name('tasks.index');
    Route::get('/tasks/create', Create::class)->name('tasks.create');
    Route::get('/tasks/{task}', Show::class)->name('tasks.show');
    Route::get('/tasks/{task}/edit', Edit::class)->name('tasks.edit');
});
