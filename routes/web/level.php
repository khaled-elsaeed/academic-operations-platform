<?php

use App\Http\Controllers\LevelController;
use Illuminate\Support\Facades\Route;

// ====================
// Level Routes
// ====================

Route::middleware(['auth'])
    ->prefix('levels')
    ->name('levels.')
    ->controller(LevelController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:level.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:level.view');
        // No middleware: not sensitive, for dropdown
        Route::get('all', 'all')->name('all');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:level.view');
        Route::get('{level}', 'show')->name('show')->middleware('can:level.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:level.create');
        
        // Update
        Route::put('{level}', 'update')->name('update')->middleware('can:level.edit');
        Route::patch('{level}', 'update')->middleware('can:level.edit');
        
        // Delete
        Route::delete('{level}', 'destroy')->name('destroy')->middleware('can:level.delete');
    }); 