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
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\EnrollmentDocumentController;

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
        Route::get('/home/stats', [AdminHomeController::class, 'stats'])->name('home.stats');

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
                Route::get('add', function() { return view('admin.available_course.add'); })->name('add');
                Route::get('datatable', 'datatable')->name('datatable');
                Route::post('/', 'store')->name('store');
                Route::put('{id}', 'update')->name('update');
                Route::delete('{id}', 'destroy')->name('destroy');
                // Import
                Route::post('import', 'import')->name('import');
                // Template Download
                Route::get('template', 'downloadTemplate')->name('template');
                // AJAX: Programs and Levels for a given available course
                Route::get('{availableCourse}/programs', 'programs')->name('programs');
                Route::get('{availableCourse}/levels', 'levels')->name('levels');
                Route::get('{availableCourse}', [App\Http\Controllers\Admin\AvailableCourseController::class, 'show'])->name('show');
            });

        // Courses (for AJAX dropdown)
        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

        // Terms (for AJAX dropdown)
        Route::get('terms', [TermController::class, 'index'])->name('terms.index');

        // Levels (for AJAX dropdown)
        Route::get('levels', [LevelController::class, 'index'])->name('levels.index');

        // Enrollments Group
        Route::prefix('enrollments')
            ->name('enrollments.')
            ->controller(\App\Http\Controllers\Admin\EnrollmentController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable');
                Route::get('stats', 'stats')->name('stats');
                Route::get('/', 'index')->name('index');
                Route::get('add', 'add')->name('add');
                Route::post('find-student', 'findStudent')->name('findStudent');
                Route::post('available-courses', 'availableCourses')->name('availableCourses');
                Route::post('/', 'store')->name('store');
                Route::put('{enrollment}', 'update')->name('update');
                Route::patch('{enrollment}', 'update');
                Route::delete('{enrollment}', 'destroy')->name('destroy');
                Route::post('student-enrollments', 'studentEnrollments')->name('studentEnrollments');
            });

        // Available Courses Edit and Update
        Route::get('available-courses', [App\Http\Controllers\Admin\AvailableCourseController::class, 'index'])->name('available_courses.index');
        Route::get('available-courses/create', [App\Http\Controllers\Admin\AvailableCourseController::class, 'create'])->name('available_courses.create');
        Route::post('available-courses', [App\Http\Controllers\Admin\AvailableCourseController::class, 'store'])->name('available_courses.store');
        Route::get('available-courses/{available_course}/edit', [App\Http\Controllers\Admin\AvailableCourseController::class, 'edit'])->name('available_courses.edit');
        Route::put('available-courses/{available_course}', [App\Http\Controllers\Admin\AvailableCourseController::class, 'update'])->name('available_courses.update');
    });

Route::get('/enrollment/download/{student}', [EnrollmentDocumentController::class, 'downloadEnrollmentDocument'])->name('enrollment.download');
