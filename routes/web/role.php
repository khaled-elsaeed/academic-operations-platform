<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// ====================
// Role Routes
// ====================

Route::middleware(['auth'])
    ->prefix('roles')
    ->name('roles.')
    ->controller(RoleController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:role.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:role.view');
        Route::get('permissions', 'getPermissions')->name('permissions')->middleware('can:role.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:role.view');
        Route::get('{role}', 'show')->name('show')->middleware('can:role.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:role.create');
        
        // Update
        Route::put('{role}', 'update')->name('update')->middleware('can:role.edit');
        Route::patch('{role}', 'update')->middleware('can:role.edit');
        
        // Delete
        Route::delete('{role}', 'destroy')->name('destroy')->middleware('can:role.delete');
    }); 