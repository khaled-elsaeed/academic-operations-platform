<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ====================
// User Routes
// ====================

Route::middleware(['auth'])
    ->prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function () {
        // ===== Specific Routes First =====
        Route::get('datatable', 'datatable')->name('datatable')->middleware('can:user.view');
        Route::get('stats', 'stats')->name('stats')->middleware('can:user.view');
        Route::get('roles', 'getRoles')->name('roles')->middleware('can:user.view');

        // ===== CRUD Operations =====
        // List & View
        Route::get('/', 'index')->name('index')->middleware('can:user.view');
        Route::get('{user}', 'show')->name('show')->middleware('can:user.view');
        
        // Create
        Route::post('/', 'store')->name('store')->middleware('can:user.create');
        
        // Update
        Route::put('{user}', 'update')->name('update')->middleware('can:user.edit');
        Route::patch('{user}', 'update')->middleware('can:user.edit');
        
        // Delete
        Route::delete('{user}', 'destroy')->name('destroy')->middleware('can:user.delete');
    }); 