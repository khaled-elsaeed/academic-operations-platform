<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

// ====================
// Enrollment Routes
// ====================

Route::middleware(['auth'])
    ->prefix('enrollments')
    ->name('enrollments.')
    ->controller(EnrollmentController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:enrollment.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:enrollment.view');
        Route::get('add', 'add')->name('add')->middleware('can:enrollment.create');
        Route::get('template', 'downloadTemplate')->name('template')->middleware('can:enrollment.view');

        // ===== Student Operations =====
        Route::post('find-student', 'findStudent')->name('findStudent')->middleware('can:enrollment.view');
        Route::post('available-courses', 'availableCourses')->name('availableCourses')->middleware('can:enrollment.view');
        Route::post('student-enrollments', 'studentEnrollments')->name('studentEnrollments')->middleware('can:enrollment.view');
        Route::get('get-schedules', 'getSchedules')->name('getSchedules')->middleware('can:enrollment.view');

        // ===== Import/Export Operations =====
        Route::post('import', 'import')->name('import')->middleware('can:enrollment.import');
        Route::get('export', 'export')->name('export')->middleware('can:enrollment.export');
        Route::post('remaining-credit-hours', 'getRemainingCreditHours')->name('remainingCreditHours')->middleware('can:enrollment.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:enrollment.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:enrollment.create');
        
        // Delete
        Route::delete('{enrollment}', 'destroy')->name('destroy')->middleware('can:enrollment.delete');
    }); 