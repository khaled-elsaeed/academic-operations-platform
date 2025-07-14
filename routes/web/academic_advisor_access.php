<?php

use App\Http\Controllers\AcademicAdvisorAccessController;
use Illuminate\Support\Facades\Route;

// ====================
// Academic Advisor Access Routes
// ====================

Route::middleware(['auth'])
    ->prefix('academic-advisor-access')
    ->name('academic_advisor_access.')
    ->controller(AcademicAdvisorAccessController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:academic_advisor_access.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:academic_advisor_access.view');
                // No middleware: not sensitive, for dropdown

        Route::get('all', 'all')->name('all');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:academic_advisor_access.view');
        Route::get('{academicAdvisorAccess}', 'show')->name('show')->middleware('can:academic_advisor_access.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:academic_advisor_access.create');
        
        // Update
        Route::put('{academicAdvisorAccess}', 'update')->name('update')->middleware('can:academic_advisor_access.edit');
        Route::patch('{academicAdvisorAccess}', 'update')->middleware('can:academic_advisor_access.edit');
        
        // Delete
        Route::delete('{academicAdvisorAccess}', 'destroy')->name('destroy')->middleware('can:academic_advisor_access.delete');
    }); 