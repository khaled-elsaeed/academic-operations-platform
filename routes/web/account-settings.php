<?php

use App\Http\Controllers\AccountSettingsController;
use Illuminate\Support\Facades\Route;

// ====================
// Account Settings Routes
// ====================

Route::middleware(['auth'])
    ->prefix('account-settings')
    ->name('account-settings.')
    ->controller(AccountSettingsController::class)
    ->group(function () {
        // Display account settings page
        Route::get('/', 'index')->name('index');
        
        // Update account settings
        Route::put('/', 'update')->name('update');
        
        // Update password
        Route::put('/password', 'updatePassword')->name('update-password');
    }); 