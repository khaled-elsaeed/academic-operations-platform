<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminHomeController;

// ====================
// Admin Home Routes
// ====================

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin Home Dashboard
        Route::get('/home', [AdminHomeController::class, 'home'])
            ->name('home')
            ->middleware('can:student.view');

        // Dashboard Statistics Endpoint
        Route::get('/home/stats', [AdminHomeController::class, 'stats'])
            ->name('home.stats')
            ->middleware('can:student.view');
    });