<?php

use App\Http\Controllers\FacultyController;
use Illuminate\Support\Facades\Route;

// ====================
// Faculty Routes
// ====================

Route::middleware(['auth'])
    ->prefix('faculties')
    ->name('faculties.')
    ->controller(FacultyController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:faculty.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:faculty.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:faculty.view');
        Route::get('{faculty}', 'show')->name('show')->middleware('can:faculty.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:faculty.create');
        
        // Update
        Route::put('{faculty}', 'update')->name('update')->middleware('can:faculty.edit');
        Route::patch('{faculty}', 'update')->middleware('can:faculty.edit');
        
        // Delete
        Route::delete('{faculty}', 'destroy')->name('destroy')->middleware('can:faculty.delete');
    }); 