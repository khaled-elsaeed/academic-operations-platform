<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

// ====================
// Permission Routes
// ====================

Route::middleware(['auth'])
    ->prefix('permissions')
    ->name('permissions.')
    ->controller(PermissionController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:permission.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:permission.view');
        Route::get('roles', 'getRoles')->name('roles')->middleware('can:permission.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:permission.view');
        Route::get('{permission}', 'show')->name('show')->middleware('can:permission.view');
    }); 