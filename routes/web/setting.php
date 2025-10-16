<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingController;

Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
// Enrollment settings API (used by the settings UI)
Route::get('/settings/enrollment', [SettingController::class, 'enrollment'])->name('settings.enrollment.get');
Route::post('/settings/enrollment', [SettingController::class, 'updateEnrollment'])->name('settings.enrollment.update');

Route::get('/settings/{key}', [SettingController::class, 'show'])->name('settings.show');
Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
Route::put('/settings/{key}', [SettingController::class, 'update'])->name('settings.update');
Route::delete('/settings/{key}', [SettingController::class, 'destroy'])->name('settings.destroy');
