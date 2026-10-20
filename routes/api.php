<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::patch('settings/profile', [SettingsController::class, 'updateProfile']);
    Route::patch('settings/preferences', [SettingsController::class, 'updatePreferences']);
    Route::patch('settings/password', [SettingsController::class, 'changePassword']);
    Route::get('settings/export', [SettingsController::class, 'exportData']);
    Route::delete('account', [SettingsController::class, 'destroyAccount']);

    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/upcoming-renewals', [DashboardController::class, 'upcomingRenewals']);

    Route::get('subscriptions/meta', [SubscriptionController::class, 'meta']);
    Route::post('subscriptions/{subscription}/mark-used', [SubscriptionController::class, 'markUsed']);
    Route::apiResource('subscriptions', SubscriptionController::class)->except(['create', 'edit']);
});

Route::get('students/statistics', [StudentController::class, 'statistics']);
Route::apiResource('students', StudentController::class)->except(['create', 'edit']);