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
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:schedule.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:schedule.view');
        Route::get('template', 'downloadTemplate')->name('template')->middleware('can:schedule.view');
        Route::post('import', 'import')->name('import')->middleware('can:schedule.import');
        Route::get('export', 'export')->name('export')->middleware('can:schedule.export');
        Route::get('/', 'index')->name('index')->middleware('can:schedule.view');
        Route::post('/', 'store')->name('store')->middleware('can:schedule.create');
        Route::delete('{schedule}', 'destroy')->name('destroy')->middleware('can:schedule.delete');
    });
