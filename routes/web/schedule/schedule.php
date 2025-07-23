<?php

use App\Http\Controllers\Schedule\ScheduleController;
use Illuminate\Support\Facades\Route;

// ====================
// Schedule Routes
// ====================

Route::middleware(['auth'])
    ->prefix('schedules')
    ->name('schedules.')
    ->controller(ScheduleController::class)
    ->group(function () {
        Route::get('datatable', 'datatable')->name('datatable');
        Route::get('stats', 'stats')->name('stats');
        Route::get('template', 'downloadTemplate')->name('template');
        Route::post('import', 'import')->name('import');
        Route::get('export', 'export')->name('export');
        Route::get('create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{schedule}', 'show')->name('show');
        Route::delete('{schedule}', 'destroy')->name('destroy');
    });
