<?php

use App\Http\Controllers\AvailableCourse\EligibilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Available Course Eligibility Routes
|--------------------------------------------------------------------------
|
| Routes for managing eligibility rules for available courses.
|
*/

Route::middleware('auth')
    ->prefix('available-courses')
    ->name('available_courses.')
    ->group(function () {
        Route::controller(EligibilityController::class)->group(function () {
            // Eligibility Management Routes
            Route::prefix('{id}/eligibilities')->name('eligibilities.')->group(function () {
                Route::get('', 'getAvailableCourseEligibilities')->name('all')->middleware('can:available_course.view');
                Route::get('datatable', 'datatable')->name('datatable')->middleware('can:available_course.view');
                Route::get('{eligibility}', 'show')->name('show')->middleware('can:available_course.view');
                Route::post('/', 'store')->name('store')->middleware('can:available_course.edit');
                Route::put('{eligibility}', 'update')->name('update')->middleware('can:available_course.edit');
                Route::delete('{eligibility}', 'delete')->name('delete')->middleware('can:available_course.edit');
            });
        });
    });