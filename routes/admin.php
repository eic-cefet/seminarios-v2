<?php

use App\Http\Controllers\Admin\AdminApiTokenController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLgpdController;
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

    // LGPD — admin can act on behalf of users via out-of-app channels (email/ANPD)
    Route::get('/users/{user}/lgpd', [AdminLgpdController::class, 'show']);
    Route::post('/users/{user}/lgpd/export', [AdminLgpdController::class, 'export']);
    Route::post('/users/{user}/lgpd/anonymize', [AdminLgpdController::class, 'anonymize']);

    Route::apiResource('locations', AdminLocationController::class);

    // Defined before apiResource for consistency with the workshop pattern below.
    // POST won't conflict with apiResource's GET /{subject}, but ordering makes intent clear.
    Route::post('/subjects/merge', [AdminSubjectController::class, 'merge']);
    Route::apiResource('subjects', AdminSubjectController::class)
        ->parameters(['subjects' => 'subject:slug']);

    // Registrations (not a full resource — only index + custom action)
    Route::get('/registrations', [AdminRegistrationController::class, 'index']);
    Route::patch('/registrations/{registration}/presence', [AdminRegistrationController::class, 'togglePresence']);

    Route::apiResource('seminars', AdminSeminarController::class);

    // Workshop search must be defined before the resource route
    Route::get('/workshops/search-seminars', [AdminWorkshopController::class, 'searchSeminars']);
    Route::apiResource('workshops', AdminWorkshopController::class)
        ->parameters(['workshops' => 'workshop:slug']);

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

    // Audit Logs
    Route::get('/audit-logs/summary', [AdminAuditLogController::class, 'summary']);
    Route::get('/audit-logs/export', [AdminAuditLogController::class, 'export']);
    Route::get('/audit-logs/event-names', [AdminAuditLogController::class, 'eventNames']);

    // API Tokens
    Route::apiResource('api-tokens', AdminApiTokenController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('/api-tokens/abilities', [AdminApiTokenController::class, 'abilities']);
    Route::post('/api-tokens/{id}/regenerate', [AdminApiTokenController::class, 'regenerate']);

    // AI
    Route::middleware('throttle:ai')->group(function () {
        Route::post('/ai/transform-text', [AiTextController::class, 'transform']);
        Route::post('/ai/suggest-merge-name', [AiTextController::class, 'suggestMergeName']);
        Route::post('/ai/suggest-subject-tags', [AiTextController::class, 'suggestSubjectTags']);
    });
    Route::get('/ai/rating-sentiments', [AiTextController::class, 'ratingSentiments']);
});
