<?php

use App\Http\Controllers\PrerequisiteExceptionController;
use Illuminate\Support\Facades\Route;

// ====================
// Prerequisite Exceptions Routes
// ====================

Route::middleware(['auth'])
    ->prefix('prerequisite-exceptions')
    ->name('prerequisite-exceptions.')
    ->controller(PrerequisiteExceptionController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:prerequisite_exception.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:prerequisite_exception.view');
        Route::get('students', 'getStudents')->name('students')->middleware('can:prerequisite_exception.view');
        Route::get('courses', 'getCourses')->name('courses')->middleware('can:prerequisite_exception.view');
        Route::get('courses/{course}/prerequisites', 'getPrerequisites')->name('prerequisites')->middleware('can:prerequisite_exception.view');
        Route::get('terms', 'getTerms')->name('terms')->middleware('can:prerequisite_exception.view');

        // ===== Import & Template (must be before {exception}) =====
        Route::post('import', 'import')->name('import')->middleware('can:prerequisite_exception.create');
        Route::get('download-template', 'downloadTemplate')->name('download-template')->middleware('can:prerequisite_exception.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:prerequisite_exception.view');
        Route::get('{exception}', 'show')->name('show')->middleware('can:prerequisite_exception.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:prerequisite_exception.create');
        
        // Update
        Route::put('{exception}', 'update')->name('update')->middleware('can:prerequisite_exception.edit');
        Route::patch('{exception}/deactivate', 'deactivate')->name('deactivate')->middleware('can:prerequisite_exception.edit');
        Route::patch('{exception}/activate', 'activate')->name('activate')->middleware('can:prerequisite_exception.edit');
        
        // Delete
        Route::delete('{exception}', 'destroy')->name('destroy')->middleware('can:prerequisite_exception.delete');

    });
