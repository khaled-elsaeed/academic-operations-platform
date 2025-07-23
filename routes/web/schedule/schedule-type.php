<?php

use App\Http\Controllers\Schedule\ScheduleTypeController;
use Illuminate\Support\Facades\Route;

// ====================
// Schedule Type Routes
// ====================

Route::middleware(['auth'])
    ->prefix('schedule-types')
    ->name('schedule-types.')
    ->controller(ScheduleTypeController::class)
    ->group(function () {
        Route::get('datatable', 'datatable')->name('datatable');
        Route::get('create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('{scheduleType}', 'destroy')->name('destroy');
        Route::get('all', 'all')->name('all');
    });
