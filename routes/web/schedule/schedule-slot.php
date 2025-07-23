<?php

use App\Http\Controllers\Schedule\ScheduleSlotController;
use Illuminate\Support\Facades\Route;

// ====================
// Schedule Slot Routes
// ====================

Route::middleware(['auth'])
    ->prefix('schedule-slots')
    ->name('schedule-slots.')
    ->controller(ScheduleSlotController::class)
    ->group(function () {
        Route::get('datatable', 'datatable')->name('datatable');
        Route::get('stats', 'stats')->name('stats');
        Route::get('/', 'index')->name('index');
        Route::get('{slot}', 'show')->name('show');
        Route::delete('{slot}', 'destroy')->name('destroy');
    });
