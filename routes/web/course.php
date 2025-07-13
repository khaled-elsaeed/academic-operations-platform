<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

// ====================
// Course Routes
// ====================

Route::middleware(['auth'])
    ->prefix('courses')
    ->name('courses.')
    ->controller(CourseController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:course.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:course.view');
        Route::get('faculties', 'getFaculties')->name('faculties')->middleware('can:course.view');
        Route::get('all', 'all')->name('all');
        Route::post('prerequisites', 'getPrerequisites')->name('prerequisites');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:course.view');
        Route::get('{course}', 'show')->name('show')->middleware('can:course.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:course.create');
        
        // Update
        Route::put('{course}', 'update')->name('update')->middleware('can:course.edit');
        Route::patch('{course}', 'update')->middleware('can:course.edit');
        
        // Delete
        Route::delete('{course}', 'destroy')->name('destroy')->middleware('can:course.delete');
    }); 