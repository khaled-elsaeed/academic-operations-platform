<?php

// ====================
// Imports
// ====================

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentDocumentController;
use Illuminate\Support\Facades\Route;

// ====================
// Include Home Routes
// ====================

require __DIR__.'/web/home.php';

// ====================
// Public Routes
// ====================

Route::group([], function () {

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
// Settings Page Route
// ====================

require __DIR__.'/web/setting.php';

// ====================
// Include Available Courses Routes
// ====================

require __DIR__ . '/web/available_course/available_course.php';
require __DIR__ . '/web/available_course/eligibility.php';
require __DIR__ . '/web/available_course/schedule.php';


// ====================
// Include Course Routes
// ====================

require __DIR__.'/web/course.php';

// ====================
// Include Program Routes
// ====================

require __DIR__.'/web/program.php';

// ====================
// Include Term Routes
// ====================

require __DIR__.'/web/term.php';

// ====================
// Include Level Routes
// ====================

require __DIR__.'/web/level.php';

// ====================
// Include Faculty Routes
// ====================

require __DIR__.'/web/faculty.php';

// ====================
// Include Permission Routes
// ====================

require __DIR__.'/web/permission.php';

// ====================
// Include Role Routes
// ====================

require __DIR__.'/web/role.php';

// ====================
// Include User Routes
// ====================

require __DIR__.'/web/user.php';

// ====================
// Include Enrollment Routes
// ====================

require __DIR__.'/web/enrollment.php';

// ====================
// Include Student Routes
// ====================

require __DIR__.'/web/student.php';

// ====================
// Include Credit Hours Exceptions Routes
// ====================

require __DIR__.'/web/credit_hours_exceptions.php';


// ====================
// Include Academic Advisor Access Routes
// ====================

require __DIR__.'/web/academic_advisor_access.php';

// ====================
// Include Account Settings Routes
// ====================

require __DIR__.'/web/account-settings.php';

// ====================
// Include Schedule Routes
// ====================

require __DIR__.'/web/schedule/schedule.php';

// ====================
// Include Schedule Type Routes
// ====================

require __DIR__.'/web/schedule/schedule-type.php';

// ====================
// Include Schedule Slot Routes
// ====================

require __DIR__.'/web/schedule/schedule-slot.php';
