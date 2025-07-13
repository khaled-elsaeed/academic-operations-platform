<?php

use App\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

// ====================
// Term Routes
// ====================

Route::middleware(['auth'])
    ->prefix('terms')
    ->name('terms.')
    ->controller(TermController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:term.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:term.view');
        // No middleware: not sensitive, for dropdown
        Route::get('all', 'all')->name('all');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:term.view');
        Route::get('{term}', 'show')->name('show')->middleware('can:term.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:term.create');
        
        // Update
        Route::put('{term}', 'update')->name('update')->middleware('can:term.edit');
        Route::patch('{term}', 'update')->middleware('can:term.edit');
        
        // Delete
        Route::delete('{term}', 'destroy')->name('destroy')->middleware('can:term.delete');
    });