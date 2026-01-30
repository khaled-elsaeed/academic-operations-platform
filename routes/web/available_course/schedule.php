<?php

use App\Http\Controllers\AvailableCourse\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Available Course Schedule Routes
|--------------------------------------------------------------------------
|
| Routes for managing schedules for available courses.
|
*/

Route::middleware('auth')
    ->prefix('available-courses')
    ->name('available_courses.')
    ->group(function () {
        Route::controller(ScheduleController::class)->group(function () {
            // Schedule Management Routes
            Route::prefix('{id}/schedules')->name('schedules.')->group(function () {
                Route::get('', 'getAvailableCourseSchedules')->name('all')->middleware('can:available_course.view');
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:available_course.view');
                Route::get('{scheduleId}', 'show')->name('show')->middleware('can:available_course.view');
                Route::post('/', 'store')->name('store')->middleware('can:available_course.edit');
                Route::put('{scheduleId}', 'update')->name('update')->middleware('can:available_course.edit');
                Route::delete('{scheduleId}', 'delete')->name('delete')->middleware('can:available_course.edit');
            });
        });
    });