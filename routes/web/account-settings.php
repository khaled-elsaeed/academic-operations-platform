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
        Route::get('/', 'index')->name('index')->middleware('can:account_settings.view');
        
        // Update account settings
        Route::put('/', 'update')->name('update')->middleware('can:account_settings.edit');
        
        // Update password
        Route::put('/password', 'updatePassword')->name('update-password')->middleware('can:account_settings.password');
    }); 