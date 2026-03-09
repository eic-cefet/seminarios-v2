<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminPresenceLinkController;
use App\Http\Controllers\Admin\AdminRegistrationController;
use App\Http\Controllers\Admin\AdminSeminarController;
use App\Http\Controllers\Admin\AdminSubjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWorkshopController;
use App\Http\Controllers\Admin\AiTextController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

    // CRUD Resources
    // apiResource registers both PUT and PATCH for update routes.
    // All update requests use 'sometimes' validation, supporting partial payloads.
    Route::apiResource('users', AdminUserController::class);
    Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->withTrashed();

    Route::apiResource('locations', AdminLocationController::class);

    // Defined before apiResource for consistency with the workshop pattern below.
    // POST won't conflict with apiResource's GET /{subject}, but ordering makes intent clear.
    Route::post('/subjects/merge', [AdminSubjectController::class, 'merge']);
    Route::apiResource('subjects', AdminSubjectController::class);

    // Registrations (not a full resource — only index + custom action)
    Route::get('/registrations', [AdminRegistrationController::class, 'index']);
    Route::patch('/registrations/{registration}/presence', [AdminRegistrationController::class, 'togglePresence']);

    Route::apiResource('seminars', AdminSeminarController::class);

    // Workshop search must be defined before the resource route
    Route::get('/workshops/search-seminars', [AdminWorkshopController::class, 'searchSeminars']);
    Route::apiResource('workshops', AdminWorkshopController::class);

    // Helper endpoints for dropdowns
    Route::get('/seminar-types', [AdminSeminarController::class, 'listTypes']);
    Route::get('/workshops-dropdown', [AdminSeminarController::class, 'listWorkshops']);
    Route::get('/locations-dropdown', [AdminSeminarController::class, 'listLocations']);

    // Presence Links (QR Code)
    Route::get('/seminars/{seminar}/presence-link', [AdminPresenceLinkController::class, 'show']);
    Route::post('/seminars/{seminar}/presence-link', [AdminPresenceLinkController::class, 'store']);
    Route::patch('/seminars/{seminar}/presence-link/toggle', [AdminPresenceLinkController::class, 'toggle']);

    // Reports
    Route::get('/reports/courses', [ReportController::class, 'courses']);
    Route::get('/reports/semestral', [ReportController::class, 'semestral']);

    // AI
    Route::middleware('throttle:ai')->group(function () {
        Route::post('/ai/transform-text', [AiTextController::class, 'transform']);
        Route::post('/ai/suggest-merge-name', [AiTextController::class, 'suggestMergeName']);
    });
    Route::get('/ai/rating-sentiments', [AiTextController::class, 'ratingSentiments']);
});
