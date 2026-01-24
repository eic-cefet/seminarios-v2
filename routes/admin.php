<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminPresenceLinkController;
use App\Http\Controllers\Admin\AdminRegistrationController;
use App\Http\Controllers\Admin\AdminSeminarController;
use App\Http\Controllers\Admin\AdminSubjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWorkshopController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

    // Users CRUD
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->withTrashed();

    // Locations CRUD
    Route::get('/locations', [AdminLocationController::class, 'index']);
    Route::post('/locations', [AdminLocationController::class, 'store']);
    Route::get('/locations/{location}', [AdminLocationController::class, 'show']);
    Route::put('/locations/{location}', [AdminLocationController::class, 'update']);
    Route::delete('/locations/{location}', [AdminLocationController::class, 'destroy']);

    // Subjects CRUD + Merge
    Route::get('/subjects', [AdminSubjectController::class, 'index']);
    Route::post('/subjects', [AdminSubjectController::class, 'store']);
    Route::get('/subjects/{subject}', [AdminSubjectController::class, 'show']);
    Route::put('/subjects/{subject}', [AdminSubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy']);
    Route::post('/subjects/merge', [AdminSubjectController::class, 'merge']);

    // Registrations (subscriptions)
    Route::get('/registrations', [AdminRegistrationController::class, 'index']);
    Route::patch('/registrations/{registration}/presence', [AdminRegistrationController::class, 'togglePresence']);

    // Seminars CRUD
    Route::get('/seminars', [AdminSeminarController::class, 'index']);
    Route::post('/seminars', [AdminSeminarController::class, 'store']);
    Route::get('/seminars/{seminar}', [AdminSeminarController::class, 'show']);
    Route::put('/seminars/{seminar}', [AdminSeminarController::class, 'update']);
    Route::delete('/seminars/{seminar}', [AdminSeminarController::class, 'destroy']);

    // Workshops CRUD
    Route::get('/workshops/search-seminars', [AdminWorkshopController::class, 'searchSeminars']);
    Route::get('/workshops', [AdminWorkshopController::class, 'index']);
    Route::post('/workshops', [AdminWorkshopController::class, 'store']);
    Route::get('/workshops/{workshop}', [AdminWorkshopController::class, 'show']);
    Route::put('/workshops/{workshop}', [AdminWorkshopController::class, 'update']);
    Route::delete('/workshops/{workshop}', [AdminWorkshopController::class, 'destroy']);

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
});
