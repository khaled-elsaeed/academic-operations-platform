<?php

use App\Http\Controllers\AvailableCourse\AvailableCourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Available Courses Routes
|--------------------------------------------------------------------------
|
| Here are all routes related to available courses management including
| CRUD operations, imports, schedules, and eligibility management.
|
*/

Route::middleware('auth')
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

        // ===== Student Specific Routes =====
        Route::post('by-student', 'availableCoursesByStudent')->name('by_student');

        // ===== Import/Export Operations =====
        Route::post('/import', 'import')->name('import')->middleware('can:available_course.import');
        Route::get('/import/status/{uuid}', 'importStatus')->name('import.status')->middleware('can:available_course.import');
        Route::post('/import/cancel/{uuid}', 'importCancel')->name('import.cancel')->middleware('can:available_course.import');
        Route::get('/import/download/{uuid}', 'importDownload')->name('import.download')->middleware('can:available_course.import');
        // Export operations
        Route::post('/export', 'export')->name('export')->middleware('can:available_course.view');
        Route::get('/export/status/{uuid}', 'exportStatus')->name('export.status')->middleware('can:available_course.view');
        Route::post('/export/cancel/{uuid}', 'exportCancel')->name('export.cancel')->middleware('can:available_course.view');
        Route::get('/export/download/{uuid}', 'exportDownload')->name('export.download')->middleware('can:available_course.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:available_course.view');
        Route::get('{id}', 'show')->name('show')->middleware('can:available_course.view');
        Route::get('{id}/edit', 'edit')->name('edit')->middleware('can:available_course.edit');

        // Create
        Route::post('/', 'store')->name('store')->middleware('can:available_course.create');

        // Update
        Route::put('{id}', 'update')->name('update')->middleware('can:available_course.edit');
        Route::patch('{id}', 'update')->middleware('can:available_course.edit');

        // Delete
        Route::delete('{id}', 'destroy')->name('destroy')->middleware('can:available_course.delete');

        // ===== Related Data Routes =====
        Route::get('{id}/programs', 'programs')->name('programs')->middleware('can:available_course.view');
        Route::get('{id}/levels', 'levels')->name('levels')->middleware('can:available_course.view');

        // ===== Edit Page Specific Routes =====
        Route::put('{id}/basic', 'updateBasic')->name('update.basic')->middleware('can:available_course.edit');
    });