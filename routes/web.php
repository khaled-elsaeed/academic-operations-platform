<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\Admin\AvailableCourseController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AcademicAdvisorAccessController;
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

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Admin Home
        Route::get('/home', [AdminHomeController::class, 'home'])->name('home')->middleware('can:student.view');
        Route::get('/home/stats', [AdminHomeController::class, 'stats'])->name('home.stats')->middleware('can:student.view');

        // Students Group
        Route::prefix('students')
            ->name('students.')
            ->controller(StudentController::class)
            ->group(function () {
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:student.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:student.view');
                Route::get('template', 'downloadTemplate')->name('template')->middleware('can:student.view');
                Route::post('import', 'import')->name('import')->middleware('can:student.create');
                Route::get('/', 'index')->name('index')->middleware('can:student.view');
                Route::get('create', 'create')->name('create')->middleware('can:student.create');
                Route::post('/', 'store')->name('store')->middleware('can:student.create');
                Route::get('{student}', 'show')->name('show')->middleware('can:student.view');
                Route::get('{student}/edit', 'edit')->name('edit')->middleware('can:student.edit');
                Route::put('{student}', 'update')->name('update')->middleware('can:student.edit');
                Route::patch('{student}', 'update')->middleware('can:student.edit');
                Route::delete('{student}', 'destroy')->name('destroy')->middleware('can:student.delete');
                Route::get('{student}/download/pdf', 'downloadPdf')->name('download.pdf')->middleware('can:student.view');
                Route::get('{student}/download/word', 'downloadWord')->name('download.word')->middleware('can:student.view');
                Route::get('{student}/download-options', 'getDownloadOptions')->name('download.options')->middleware('can:student.view');
            });

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
                Route::get('programs', 'getPrograms')->name('programs')->middleware('can:course.view');
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

        // Available Courses Group
        Route::prefix('available-courses')
            ->name('available_courses.')
            ->controller(AvailableCourseController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index')->middleware('can:course.view');
                Route::get('add', function() { return view('admin.available_course.add'); })->name('add')->middleware('can:course.create');
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:course.view');
                Route::post('/', 'store')->name('store')->middleware('can:course.create');
                Route::put('{id}', 'update')->name('update')->middleware('can:course.edit');
                Route::delete('{id}', 'destroy')->name('destroy')->middleware('can:course.delete');
                Route::post('import', 'import')->name('import')->middleware('can:course.create');
                Route::get('template', 'downloadTemplate')->name('template')->middleware('can:course.view');
                Route::get('{availableCourse}/programs', 'programs')->name('programs')->middleware('can:course.view');
                Route::get('{availableCourse}/levels', 'levels')->name('levels')->middleware('can:course.view');
                Route::get('{availableCourse}', [App\Http\Controllers\Admin\AvailableCourseController::class, 'show'])->name('show')->middleware('can:course.view');
            });

        // Terms (for AJAX dropdown)
        Route::get('terms', [TermController::class, 'index'])->name('terms.index');

        // Levels (for AJAX dropdown)
        Route::get('levels', [LevelController::class, 'index'])->name('levels.index');

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
                Route::put('{enrollment}', 'update')->name('update')->middleware('can:enrollment.edit');
                Route::patch('{enrollment}', 'update')->middleware('can:enrollment.edit');
                Route::delete('{enrollment}', 'destroy')->name('destroy')->middleware('can:enrollment.delete');
                Route::post('student-enrollments', 'studentEnrollments')->name('studentEnrollments')->middleware('can:enrollment.view');
            });

        // Available Courses Edit and Update
        Route::get('available-courses', [App\Http\Controllers\Admin\AvailableCourseController::class, 'index'])->name('available_courses.index');
        Route::get('available-courses/create', [App\Http\Controllers\Admin\AvailableCourseController::class, 'create'])->name('available_courses.create');
        Route::post('available-courses', [App\Http\Controllers\Admin\AvailableCourseController::class, 'store'])->name('available_courses.store');
        Route::get('available-courses/{available_course}/edit', [App\Http\Controllers\Admin\AvailableCourseController::class, 'edit'])->name('available_courses.edit');
        Route::put('available-courses/{available_course}', [App\Http\Controllers\Admin\AvailableCourseController::class, 'update'])->name('available_courses.update');

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
                Route::post('/', 'store')->name('store')->middleware('can:permission.create');
                Route::post('bulk-create', 'bulkCreate')->name('bulk-create')->middleware('can:permission.create');
                Route::get('{permission}', 'show')->name('show')->middleware('can:permission.view');
                Route::put('{permission}', 'update')->name('update')->middleware('can:permission.edit');
                Route::patch('{permission}', 'update')->middleware('can:permission.edit');
                Route::delete('{permission}', 'destroy')->name('destroy')->middleware('can:permission.delete');
            });

        // Advisor Access Group
        Route::prefix('academic-advisor-access')
            ->name('academic_advisor_access.')
            ->controller(AcademicAdvisorAccessController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index')->middleware('can:user.view');
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:user.view');
                Route::get('stats', 'stats')->name('stats')->middleware('can:user.view');
                Route::get('advisors', 'getAdvisors')->name('advisors')->middleware('can:user.view');
                Route::post('/', 'store')->name('store')->middleware('can:user.create');
                Route::get('{academicAdvisorAccess}', 'show')->name('show')->middleware('can:user.view');
                Route::put('{academicAdvisorAccess}', 'update')->name('update')->middleware('can:user.edit');
                Route::patch('{academicAdvisorAccess}', 'update')->middleware('can:user.edit');
                Route::delete('{academicAdvisorAccess}', 'destroy')->name('destroy')->middleware('can:user.delete');
            });
    });

Route::get('/enrollment/download/{student}', [EnrollmentDocumentController::class, 'downloadEnrollmentDocument'])->name('enrollment.download');
// Add new routes for PDF invoice and users PDF
Route::get('/pdf/invoice', [App\Http\Controllers\PdfController::class, 'invoice'])->name('pdf.invoice');
