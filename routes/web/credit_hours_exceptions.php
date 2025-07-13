<?php

use App\Http\Controllers\CreditHoursExceptionController;
use Illuminate\Support\Facades\Route;

// ====================
// Credit Hours Exceptions Routes
// ====================

Route::middleware(['auth'])
    ->prefix('credit-hours-exceptions')
    ->name('credit-hours-exceptions.')
    ->controller(CreditHoursExceptionController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:credit_hours_exception.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:credit_hours_exception.view');
        Route::get('students', 'getStudents')->name('students')->middleware('can:credit_hours_exception.view');
        Route::get('terms', 'getTerms')->name('terms')->middleware('can:credit_hours_exception.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:credit_hours_exception.view');
        Route::get('{exception}', 'show')->name('show')->middleware('can:credit_hours_exception.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:credit_hours_exception.create');
        
        // Update
        Route::put('{exception}', 'update')->name('update')->middleware('can:credit_hours_exception.edit');
        Route::patch('{exception}/deactivate', 'deactivate')->name('deactivate')->middleware('can:credit_hours_exception.edit');
        Route::patch('{exception}/activate', 'activate')->name('activate')->middleware('can:credit_hours_exception.edit');
        
        // Delete
        Route::delete('{exception}', 'destroy')->name('destroy')->middleware('can:credit_hours_exception.delete');
    }); 