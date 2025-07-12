<?php

use App\Http\Controllers\CreditHoursExceptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::prefix('credit-hours-exceptions')->name('credit-hours-exceptions.')->group(function () {
        Route::get('/', [CreditHoursExceptionController::class, 'index'])->name('index');
        Route::get('/datatable', [CreditHoursExceptionController::class, 'datatable'])->name('datatable');
        Route::get('/stats', [CreditHoursExceptionController::class, 'stats'])->name('stats');
        Route::get('/students', [CreditHoursExceptionController::class, 'getStudents'])->name('students');
        Route::get('/terms', [CreditHoursExceptionController::class, 'getTerms'])->name('terms');
        Route::post('/', [CreditHoursExceptionController::class, 'store'])->name('store');
        
        Route::prefix('{exception}')->group(function () {
            Route::get('/', [CreditHoursExceptionController::class, 'show'])->name('show');
            Route::put('/', [CreditHoursExceptionController::class, 'update'])->name('update');
            Route::patch('/deactivate', [CreditHoursExceptionController::class, 'deactivate'])->name('deactivate');
            Route::delete('/', [CreditHoursExceptionController::class, 'destroy'])->name('destroy');
        });
    });
}); 