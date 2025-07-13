<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// ====================
// Home Routes
// ====================

// ===== Main Home Route =====
Route::middleware(['auth', 'can:home.view'])
    ->get('/', [HomeController::class, '__invoke'])
    ->name('home');

// ===== Admin Home Routes =====
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Specific Routes First
        Route::get('home/stats', [HomeController::class, 'adminStats'])
            ->name('home.stats')
            ->middleware('can:home.admin');

        // Main Routes
        Route::get('home', [HomeController::class, 'adminHome'])
            ->name('home')
            ->middleware('can:home.admin');
    });

// ===== Advisor Home Routes =====
Route::middleware(['auth'])
    ->prefix('advisor')
    ->name('advisor.')
    ->group(function () {
        // Specific Routes First
        Route::get('home/stats', [HomeController::class, 'advisorStats'])
            ->name('home.stats')
            ->middleware('can:home.advisor');

        // Main Routes
        Route::get('home', [HomeController::class, 'advisorHome'])
            ->name('home')
            ->middleware('can:home.advisor');
    }); 