<?php

use App\Http\Controllers\AvailableCourseController;
use Illuminate\Support\Facades\Route;

// ====================
// Available Courses Routes
// ====================

Route::middleware(['auth'])
    ->prefix('available-courses')
    ->name('available_courses.')
    ->controller(AvailableCourseController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:course.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:course.view');
        Route::get('template', 'template')->name('template')->middleware('can:course.view');
        Route::get('create', 'create')->name('create')->middleware('can:course.create');
        Route::get('all', 'all')->name('all');
        
        // ===== Import/Export Operations =====
        Route::post('import', 'import')->name('import')->middleware('can:course.create');
        Route::post('remaining-credit-hours', 'getRemainingCreditHours')->name('remaining-credit-hours');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:course.view');
        Route::get('{availableCourse}', 'show')->name('show')->middleware('can:course.view');
        Route::get('{availableCourse}/edit', 'edit')->name('edit')->middleware('can:course.edit');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:course.create');
        
        // Update
        Route::put('{id}', 'update')->name('update')->middleware('can:course.edit');
        Route::patch('{id}', 'update')->middleware('can:course.edit');
        
        // Delete
        Route::delete('{id}', 'destroy')->name('destroy')->middleware('can:course.delete');

        // ===== Related Data Routes =====
        Route::get('{availableCourse}/programs', 'programs')->name('programs')->middleware('can:course.view');
        Route::get('{availableCourse}/levels', 'levels')->name('levels')->middleware('can:course.view');
    });
