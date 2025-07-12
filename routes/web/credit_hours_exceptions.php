<?php

use App\Http\Controllers\CreditHoursExceptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('credit-hours-exceptions')->name('credit-hours-exceptions.')->group(function () {
        Route::get('/', [CreditHoursExceptionController::class, 'index'])->name('index')->middleware('can:credit_hours_exception.view');
        Route::get('/datatable', [CreditHoursExceptionController::class, 'datatable'])->name('datatable')->middleware('can:credit_hours_exception.view');
        Route::get('/stats', [CreditHoursExceptionController::class, 'stats'])->name('stats')->middleware('can:credit_hours_exception.view');
        Route::get('/students', [CreditHoursExceptionController::class, 'getStudents'])->name('students')->middleware('can:credit_hours_exception.view');
        Route::get('/terms', [CreditHoursExceptionController::class, 'getTerms'])->name('terms')->middleware('can:credit_hours_exception.view');
        Route::post('/', [CreditHoursExceptionController::class, 'store'])->name('store')->middleware('can:credit_hours_exception.create');
        
        Route::prefix('{exception}')->group(function () {
            Route::get('/', [CreditHoursExceptionController::class, 'show'])->name('show')->middleware('can:credit_hours_exception.view');
            Route::put('/', [CreditHoursExceptionController::class, 'update'])->name('update')->middleware('can:credit_hours_exception.edit');
            Route::patch('/deactivate', [CreditHoursExceptionController::class, 'deactivate'])->name('deactivate')->middleware('can:credit_hours_exception.edit');
            Route::patch('/activate', [CreditHoursExceptionController::class, 'activate'])->name('activate')->middleware('can:credit_hours_exception.edit');
            Route::delete('/', [CreditHoursExceptionController::class, 'destroy'])->name('destroy')->middleware('can:credit_hours_exception.delete');
        });
    });
}); 