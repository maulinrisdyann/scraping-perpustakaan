<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrackedUrlController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'createLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'createReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', [DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::patch('/dashboard/automation/toggle', [DashboardController::class, 'toggleAutomation'])->name('dashboard.automation.toggle');
    Route::get('/ulasan', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/ulasan/monggo-lapor', [ReviewController::class, 'storeMonggoLapor'])->name('reviews.monggo-lapor.store');

    Route::get('/sumber-pantauan', [TrackedUrlController::class, 'index'])->name('tracked-urls.index');
    Route::post('/sumber-pantauan', [TrackedUrlController::class, 'store'])->name('tracked-urls.store');
    Route::get('/sumber-pantauan/{trackedUrl}/export', [TrackedUrlController::class, 'export'])->name('tracked-urls.export');
    Route::patch('/sumber-pantauan/{trackedUrl}/toggle', [TrackedUrlController::class, 'toggle'])->name('tracked-urls.toggle');

    Route::get('/komplain', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::patch('/komplain/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');

    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/pengaturan/akun', [SettingsController::class, 'store'])->name('settings.store');
    Route::patch('/pengaturan/akun/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');

    Route::get('/history-log', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
