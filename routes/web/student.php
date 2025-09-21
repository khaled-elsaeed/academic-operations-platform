<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// ====================
// Student Routes
// ====================

Route::middleware(['auth'])
    ->prefix('students')
    ->name('students.')
    ->controller(StudentController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:student.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:student.view');
        Route::get('create', 'create')->name('create')->middleware('can:student.create');
        Route::get('template', 'downloadTemplate')->name('template')->middleware('can:student.view');

        // ===== Import/Export Operations =====
        Route::post('import', 'import')->name('import')->middleware('can:student.import');
        Route::get('export', 'export')->name('export')->middleware('can:student.export');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:student.view');
        Route::get('{student}', 'show')->name('show')->middleware('can:student.view');
        Route::get('{student}/edit', 'edit')->name('edit')->middleware('can:student.edit');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:student.create');
        
        // Update
        Route::put('{student}', 'update')->name('update')->middleware('can:student.edit');
        Route::patch('{student}', 'update')->middleware('can:student.edit');
        
        // Delete
        Route::delete('{student}', 'destroy')->name('destroy')->middleware('can:student.delete');

        // ===== Download Operations =====
    Route::get('{student}/download/pdf', 'downloadPdf')->name('download.pdf')->middleware('can:student.download');
    Route::get('{student}/download/word', 'downloadWord')->name('download.word')->middleware('can:student.download');
    // Timetable download (server-side generated PDF)
    Route::get('{student}/download/timetable', 'downloadTimetable')->name('download.timetable')->middleware('can:student.download');
    });