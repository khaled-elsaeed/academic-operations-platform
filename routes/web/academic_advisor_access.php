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
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:user.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:user.view');
                // No middleware: not sensitive, for dropdown

        Route::get('all', 'all')->name('all');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:user.view');
        Route::get('{academicAdvisorAccess}', 'show')->name('show')->middleware('can:user.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:user.create');
        
        // Update
        Route::put('{academicAdvisorAccess}', 'update')->name('update')->middleware('can:user.edit');
        Route::patch('{academicAdvisorAccess}', 'update')->middleware('can:user.edit');
        
        // Delete
        Route::delete('{academicAdvisorAccess}', 'destroy')->name('destroy')->middleware('can:user.delete');
    }); 