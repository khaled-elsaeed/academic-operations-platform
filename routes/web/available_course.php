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
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:available_course.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:available_course.view');
        Route::get('template', 'template')->name('template')->middleware('can:available_course.view');
        Route::get('create', 'create')->name('create')->middleware('can:available_course.create');
        Route::get('all', 'all')->name('all');
        
        // ===== Import/Export Operations =====
        Route::post('import', 'import')->name('import')->middleware('can:available_course.import');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:available_course.view');
        Route::get('{availableCourse}', 'show')->name('show')->middleware('can:available_course.view');
        Route::get('{availableCourse}/edit', 'edit')->name('edit')->middleware('can:available_course.edit');

        // Create
        Route::post('/', 'store')->name('store')->middleware('can:available_course.create');

        // Update
        Route::put('{id}', 'update')->name('update')->middleware('can:available_course.edit');
        Route::patch('{id}', 'update')->middleware('can:available_course.edit');

        // Delete
        Route::delete('{id}', 'destroy')->name('destroy')->middleware('can:available_course.delete');

        // ===== Related Data Routes =====
        Route::get('{availableCourse}/programs', 'programs')->name('programs')->middleware('can:available_course.view');
        Route::get('{availableCourse}/levels', 'levels')->name('levels')->middleware('can:available_course.view');

        // ===== Schedules & Eligibilities AJAX Routes =====
        Route::get('{availableCourse}/schedules', 'schedules')->name('schedules')->middleware('can:available_course.view');
        Route::get('{availableCourse}/eligibilities', 'eligibilities')->name('eligibilities')->middleware('can:available_course.view');

        // ===== Edit Page Specific Routes =====
        Route::put('{availableCourse}/basic', 'updateBasic')->name('update.basic')->middleware('can:available_course.edit');

        // Eligibility Management Routes
        Route::prefix('{availableCourse}/eligibility')->name('eligibility.')->group(function () {
            Route::get('datatable', 'eligibilityDatatable')->name('datatable')->middleware('can:available_course.view');
            Route::post('/', 'storeEligibility')->name('store')->middleware('can:available_course.edit');
            Route::delete('{eligibility}', 'deleteEligibility')->name('delete')->middleware('can:available_course.edit');
        });

        // Schedule Management Routes
        Route::prefix('{availableCourse}/schedules')->name('schedules.')->group(function () {
            Route::get('datatable', 'schedulesDatatable')->name('datatable')->middleware('can:available_course.view');
            Route::get('{scheduleId}', 'showSchedule')->name('show')->middleware('can:available_course.view');
            Route::post('/', 'storeSchedule')->name('store')->middleware('can:available_course.edit');
            Route::put('{scheduleId}', 'updateSchedule')->name('update')->middleware('can:available_course.edit');
            Route::delete('{scheduleId}', 'deleteSchedule')->name('delete')->middleware('can:available_course.edit');
        });
    });
