<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// ====================
// Student Routes
// ====================

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Students Group
        Route::prefix('students')
            ->name('students.')
            ->controller(StudentController::class)
            ->group(function () {
                // Data & Stats
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:student.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:student.view');

                // Import/Export
                Route::get('template', 'downloadTemplate')->name('template')->middleware('can:student.view');
                Route::post('import', 'import')->name('import')->middleware('can:student.create');

                // CRUD
                Route::get('/', 'index')->name('index')->middleware('can:student.view');
                Route::get('create', 'create')->name('create')->middleware('can:student.create');
                Route::post('/', 'store')->name('store')->middleware('can:student.create');
                Route::get('{student}', 'show')->name('show')->middleware('can:student.view');
                Route::get('{student}/edit', 'edit')->name('edit')->middleware('can:student.edit');
                Route::put('{student}', 'update')->name('update')->middleware('can:student.edit');
                Route::patch('{student}', 'update')->middleware('can:student.edit');
                Route::delete('{student}', 'destroy')->name('destroy')->middleware('can:student.delete');

                // Downloads
                Route::get('{student}/download/pdf', 'downloadPdf')->name('download.pdf')->middleware('can:student.view');
                Route::get('{student}/download/word', 'downloadWord')->name('download.word')->middleware('can:student.view');
                Route::get('{student}/download-options', 'getDownloadOptions')->name('download.options')->middleware('can:student.view');
            });

    });