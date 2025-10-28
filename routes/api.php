<?php

use App\Http\Controllers\Api\ScheduledTaskController;
use App\Http\Controllers\Api\ScheduledTaskSilenceController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamSilenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1')->name('api.v1.')->group(function () {
        Route::get('teams', [TeamController::class, 'index'])->name('teams.index');

        Route::get('tasks', [ScheduledTaskController::class, 'index'])->name('tasks.index');
        Route::post('tasks', [ScheduledTaskController::class, 'store'])->name('tasks.store');
        Route::post('tasks/{scheduledTask}/silence', ScheduledTaskSilenceController::class)->name('tasks.silence');

        Route::post('teams/{team}/silence', TeamSilenceController::class)->name('teams.silence');
    });
});
