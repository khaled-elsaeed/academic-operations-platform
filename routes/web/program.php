<?php

use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

// ====================
// Program Routes
// ====================

Route::middleware(['auth'])
    ->prefix('programs')
    ->name('programs.')
    ->controller(ProgramController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:program.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:program.view');
        Route::get('faculties', 'getFaculties')->name('faculties')->middleware('can:program.view');
        // No middleware: not sensitive, for dropdown
        Route::get('all', 'all')->name('all');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:program.view');
        Route::get('{program}', 'show')->name('show')->middleware('can:program.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:program.create');
        
        // Update
        Route::put('{program}', 'update')->name('update')->middleware('can:program.edit');
        Route::patch('{program}', 'update')->middleware('can:program.edit');
        
        // Delete
        Route::delete('{program}', 'destroy')->name('destroy')->middleware('can:program.delete');
    }); 