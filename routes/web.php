<?php

// ====================
// Imports
// ====================

use App\Http\Controllers\Admin\AcademicAdvisorAccessController;
use App\Http\Controllers\Admin\AvailableCourseController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TermController as AdminTermController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

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
// Include Available Courses Routes
// ====================

require __DIR__.'/web/available_course.php';

// Available Courses (for AJAX dropdown - legacy)
Route::prefix('available-courses-legacy')->group(function () {
    Route::post('/', [App\Http\Controllers\AvailableCourseController::class, 'index'])->name('available-courses.legacy.index');
    Route::post('remaining-credit-hours', [App\Http\Controllers\AvailableCourseController::class, 'getRemainingCreditHours'])->name('available-courses.legacy.remaining-credit-hours');
});

// ====================
// Include Admin Home Routes
// ====================

require __DIR__.'/web/admin_home.php';

// ====================
// Include Student Routes
// ====================

require __DIR__.'/web/student.php';

// ====================
// Include Credit Hours Exceptions Routes
// ====================

require __DIR__.'/web/credit_hours_exceptions.php';

// ====================
// Admin Routes
// ====================

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {



        // Faculties Group
        Route::prefix('faculties')
            ->name('faculties.')
            ->controller(FacultyController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:faculty.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:faculty.view');
                Route::get('/', 'index')->name('index')->middleware('can:faculty.view');
                Route::post('/', 'store')->name('store')->middleware('can:faculty.create');
                Route::get('{faculty}', 'show')->name('show')->middleware('can:faculty.view');
                Route::put('{faculty}', 'update')->name('update')->middleware('can:faculty.edit');
                Route::patch('{faculty}', 'update')->middleware('can:faculty.edit');
                Route::delete('{faculty}', 'destroy')->name('destroy')->middleware('can:faculty.delete');
            });

        // Programs Group
        Route::prefix('programs')
            ->name('programs.')
            ->controller(AdminProgramController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:program.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:program.view');
                Route::get('faculties', 'getFaculties')->name('faculties')->middleware('can:program.view');
                Route::get('/', 'index')->name('index')->middleware('can:program.view');
                Route::post('/', 'store')->name('store')->middleware('can:program.create');
                Route::get('{program}', 'show')->name('show')->middleware('can:program.view');
                Route::put('{program}', 'update')->name('update')->middleware('can:program.edit');
                Route::patch('{program}', 'update')->middleware('can:program.edit');
                Route::delete('{program}', 'destroy')->name('destroy')->middleware('can:program.delete');
            });

        // Courses Group
        Route::prefix('courses')
            ->name('courses.')
            ->controller(AdminCourseController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:course.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:course.view');
                Route::get('faculties', 'getFaculties')->name('faculties')->middleware('can:course.view');
                Route::get('/', 'index')->name('index')->middleware('can:course.view');
                Route::post('/', 'store')->name('store')->middleware('can:course.create');
                Route::get('{course}', 'show')->name('show')->middleware('can:course.view');
                Route::put('{course}', 'update')->name('update')->middleware('can:course.edit');
                Route::patch('{course}', 'update')->middleware('can:course.edit');
                Route::delete('{course}', 'destroy')->name('destroy')->middleware('can:course.delete');
            });

        // Programs (for AJAX dropdown - legacy)
        Route::prefix('programs-legacy')->group(function () {
            Route::get('/', [ProgramController::class, 'index'])->name('programs.legacy.index');
        });

        // Courses (for AJAX dropdown - legacy)
        Route::prefix('courses-legacy')->group(function () {
            Route::get('/', [CourseController::class, 'index'])->name('courses.legacy.index');
        });

        // Course Prerequisites
        Route::prefix('courses')->group(function () {
            Route::post('prerequisites', [CourseController::class, 'getPrerequisites'])->name('courses.prerequisites');
        });

        // Terms Group
        Route::prefix('terms')
            ->name('terms.')
            ->controller(AdminTermController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:term.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:term.view');
                Route::get('/', 'index')->name('index')->middleware('can:term.view');
                Route::post('/', 'store')->name('store')->middleware('can:term.create');
                Route::get('{term}', 'show')->name('show')->middleware('can:term.view');
                Route::put('{term}', 'update')->name('update')->middleware('can:term.edit');
                Route::patch('{term}', 'update')->middleware('can:term.edit');
                Route::delete('{term}', 'destroy')->name('destroy')->middleware('can:term.delete');
            });

        // Terms (for AJAX dropdown - legacy)
        Route::prefix('terms-legacy')->group(function () {
            Route::get('/', [TermController::class, 'index'])->name('terms.legacy.index');
        });

        // Levels (for AJAX dropdown - legacy)
        Route::prefix('levels-legacy')->group(function () {
            Route::get('/', [LevelController::class, 'index'])->name('levels.legacy.index');
        });


         // Advisors (for AJAX dropdown - legacy)
         Route::prefix('advisors-legacy')->group(function () {
            Route::get('/', [\App\Http\Controllers\AdvisorStudentAccessController::class, 'index'])->name('advisors.legacy.index');
        });


        // Enrollments Group
        Route::prefix('enrollments')
            ->name('enrollments.')
            ->controller(\App\Http\Controllers\Admin\EnrollmentController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:enrollment.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:enrollment.view');
                Route::get('/', 'index')->name('index')->middleware('can:enrollment.view');
                Route::get('add', 'add')->name('add')->middleware('can:enrollment.create');
                Route::post('find-student', 'findStudent')->name('findStudent')->middleware('can:enrollment.view');
                Route::post('available-courses', 'availableCourses')->name('availableCourses')->middleware('can:enrollment.view');
                Route::post('/', 'store')->name('store')->middleware('can:enrollment.create');
                Route::delete('{enrollment}', 'destroy')->name('destroy')->middleware('can:enrollment.delete');
                Route::post('student-enrollments', 'studentEnrollments')->name('studentEnrollments')->middleware('can:enrollment.view');
                Route::post('import', 'import')->name('import')->middleware('can:enrollment.create');
                Route::get('template', 'downloadTemplate')->name('template')->middleware('can:enrollment.view');
            });

        // Users Group
        Route::prefix('users')
            ->name('users.')
            ->controller(UserController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:user.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:user.view');
                Route::get('roles', 'getRoles')->name('roles')->middleware('can:user.view');
                Route::get('/', 'index')->name('index')->middleware('can:user.view');
                Route::post('/', 'store')->name('store')->middleware('can:user.create');
                Route::get('{user}', 'show')->name('show')->middleware('can:user.view');
                Route::put('{user}', 'update')->name('update')->middleware('can:user.edit');
                Route::patch('{user}', 'update')->middleware('can:user.edit');
                Route::delete('{user}', 'destroy')->name('destroy')->middleware('can:user.delete');
            });

        // Roles Group
        Route::prefix('roles')
            ->name('roles.')
            ->controller(RoleController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:role.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:role.view');
                Route::get('permissions', 'getPermissions')->name('permissions')->middleware('can:role.view');
                Route::get('/', 'index')->name('index')->middleware('can:role.view');
                Route::post('/', 'store')->name('store')->middleware('can:role.create');
                Route::get('{role}', 'show')->name('show')->middleware('can:role.view');
                Route::put('{role}', 'update')->name('update')->middleware('can:role.edit');
                Route::patch('{role}', 'update')->middleware('can:role.edit');
                Route::delete('{role}', 'destroy')->name('destroy')->middleware('can:role.delete');
            });

        // Permissions Group
        Route::prefix('permissions')
            ->name('permissions.')
            ->controller(PermissionController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:permission.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:permission.view');
                Route::get('roles', 'getRoles')->name('roles')->middleware('can:permission.view');
                Route::get('/', 'index')->name('index')->middleware('can:permission.view');
                Route::get('{permission}', 'show')->name('show')->middleware('can:permission.view');
            });

        // Advisor Access Group
        Route::prefix('academic-advisor-access')
            ->name('academic_advisor_access.')
            ->controller(AcademicAdvisorAccessController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index')->middleware('can:user.view');
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:user.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:user.view');
                Route::post('/', 'store')->name('store')->middleware('can:user.create');
                Route::get('{academicAdvisorAccess}', 'show')->name('show')->middleware('can:user.view');
                Route::put('{academicAdvisorAccess}', 'update')->name('update')->middleware('can:user.edit');
                Route::patch('{academicAdvisorAccess}', 'update')->middleware('can:user.edit');
                Route::delete('{academicAdvisorAccess}', 'destroy')->name('destroy')->middleware('can:user.delete');
            });


    });



// ====================
// Advisor Routes
// ====================

Route::middleware(['auth'])->prefix('advisor')->name('advisor.')->group(function () {
    Route::get('/home', [\App\Http\Controllers\Advisor\HomeController::class, 'home'])->name('home');
    Route::get('/home/stats', [\App\Http\Controllers\Advisor\HomeController::class, 'stats'])->name('home.stats');
});

// ====================
// Miscellaneous Routes
// ====================

Route::middleware(['auth'])->get('/home', [HomeController::class, '__invoke'])->name('home.redirect');
Route::get('/enrollment/download/{student}', [EnrollmentDocumentController::class, 'downloadEnrollmentDocument'])->name('enrollment.download');
Route::get('/pdf/invoice', [\App\Http\Controllers\PdfController::class, 'invoice'])->name('pdf.invoice');
