<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\EventFeedController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventGroupController;

Route::get('/', function () {
    return redirect()->route('calendar');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

    // Profile (Breeze expects these)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('api')->group(function () {
        // Events
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::patch('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);

        // Event Groups
        Route::get('/event-groups', [EventGroupController::class, 'index']);
        Route::middleware('can:admin')->group(function () {
            Route::post('/event-groups', [EventGroupController::class, 'store']);
            Route::patch('/event-groups/{eventGroup}', [EventGroupController::class, 'update']);
            Route::delete('/event-groups/{eventGroup}', [EventGroupController::class, 'destroy']);
        });
    });

    // Backward-compat Phase 1 endpoint (optional)
    // Kalau Phase 1 kamu pakai /events/feed, bisa dialihkan:
    Route::get('/events/feed', [EventController::class, 'index']);
});
require __DIR__.'/auth.php';
