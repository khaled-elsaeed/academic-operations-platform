<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

// ====================
// Enrollment Routes
// ====================

Route::middleware('auth')
    ->prefix('enrollments')
    ->name('enrollments.')
    ->controller(EnrollmentController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:enrollment.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:enrollment.view');
        Route::get('add', 'add')->name('add')->middleware('can:enrollment.create');
        // Legacy/old enrollment page (grade-only flow)
        Route::get('add-old', 'addOld')->name('add.old')->middleware('can:enrollment.create');
        Route::get('template', 'downloadTemplate')->name('template')->middleware('can:enrollment.view');

        // ===== Student Operations =====
        Route::post('available-courses', 'availableCourses')->name('availableCourses')->middleware('can:enrollment.view');
        Route::post('student-enrollments', 'getEnrollmentsByStudent')->name('studentEnrollments')->middleware('can:enrollment.view');
        Route::post('guiding', 'getGuiding')->name('guiding')->middleware('can:enrollment.view');

        // ===== Import/Export Operations =====
        Route::post('import', 'import')->name('import')->middleware('can:enrollment.import');
        Route::get('/import/status/{uuid}', 'importStatus')->name('import.status')->middleware('can:enrollment.import');
        Route::post('/import/cancel/{uuid}', 'importCancel')->name('import.cancel')->middleware('can:enrollment.import');
        Route::get('/import/download/{uuid}', 'importDownload')->name('import.download')->middleware('can:enrollment.import');
        // Export operations
        Route::post('export', 'export')->name('export')->middleware('can:enrollment.export');
        Route::get('/export/status/{uuid}', 'exportStatus')->name('export.status')->middleware('can:enrollment.export');
        Route::post('/export/cancel/{uuid}', 'exportCancel')->name('export.cancel')->middleware('can:enrollment.export');
        Route::get('/export/download/{uuid}', 'exportDownload')->name('export.download')->middleware('can:enrollment.export');
        // Export multiple enrollment documents page & action
        Route::get('export-documents', 'exportDocumentsPage')->name('exportDocuments.page')->middleware('can:enrollment.export');
        Route::post('export-documents', 'exportDocuments')->name('exportDocuments')->middleware('can:enrollment.export');
        Route::post('remaining-credit-hours', 'getRemainingCreditHours')->name('remainingCreditHours')->middleware('can:enrollment.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:enrollment.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:enrollment.create');
        // Create (grade-only / without schedule) - separate endpoint
        Route::post('store-without-schedule', 'storeWithoutSchedule')->name('storeWithoutSchedule')->middleware('can:enrollment.create');
            
        // Delete
        Route::delete('{enrollment}', 'destroy')->name('destroy')->middleware('can:enrollment.delete');
    }); 