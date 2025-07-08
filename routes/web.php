<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\Admin\AvailableCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TermController;

// ====================
// Public Routes
// ====================

Route::group([], function () {
    // Home
    Route::get('/', HomeController::class)->name('home');

    // Authentication
    Route::prefix('login')->group(function () {
        Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/', [AuthController::class, 'login']);
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Password Reset
    Route::prefix('password')->group(function () {
        Route::get('reset', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
        Route::post('reset', [AuthController::class, 'reset'])->name('password.update');
    });
});

// ====================
// Admin Routes
// ====================

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Admin Home
        Route::get('/home', [AdminHomeController::class, 'home'])->name('home');

        // Students Group
        Route::prefix('students')
            ->name('students.')
            ->controller(StudentController::class)
            ->group(function () {
                // Data & Stats
                Route::get('datatable', 'datatable')->name('datatable');
                Route::get('stats', 'stats')->name('stats');
                // Import/Export
                Route::get('template', 'downloadTemplate')->name('template');
                Route::post('import', 'import')->name('import');
                // CRUD
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{student}', 'show')->name('show');
                Route::get('{student}/edit', 'edit')->name('edit');
                Route::put('{student}', 'update')->name('update');
                Route::patch('{student}', 'update');
                Route::delete('{student}', 'destroy')->name('destroy');
            });

        // Programs (for AJAX dropdown)
        Route::prefix('programs')->group(function () {
            Route::get('/', [ProgramController::class, 'index'])->name('programs.index');
        });

        // Available Courses Group
        Route::prefix('available-courses')
            ->name('available_courses.')
            ->controller(AvailableCourseController::class)
            ->group(function () {
                // CRUD
                Route::get('/', 'index')->name('index');
                Route::get('datatable', 'datatable')->name('datatable');
                Route::post('/', 'store')->name('store');
                Route::put('{id}', 'update')->name('update');
                Route::delete('{id}', 'destroy')->name('destroy');
                // Import
                Route::post('import', 'import')->name('import');
                // Template Download
                Route::get('template', 'downloadTemplate')->name('template');
            });

        // Courses (for AJAX dropdown)
        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

        // Terms (for AJAX dropdown)
        Route::get('terms', [TermController::class, 'index'])->name('terms.index');
    });
