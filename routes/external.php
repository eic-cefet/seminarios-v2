<?php

use App\Http\Controllers\External\ExternalLocationController;
use App\Http\Controllers\External\ExternalSeminarController;
use App\Http\Controllers\External\ExternalSeminarTypeController;
use App\Http\Controllers\External\ExternalSpeakerDataController;
use App\Http\Controllers\External\ExternalUserController;
use App\Http\Controllers\External\ExternalWorkshopController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin', 'external.conditional', 'external.idempotency'])
    ->prefix('external/v1')
    ->name('api.external.')
    ->group(function () {
        // Seminars
        Route::middleware('ability:seminars:read')->group(function () {
            Route::get('seminars', [ExternalSeminarController::class, 'index'])->name('seminars.index');
            Route::get('seminars/{seminar:slug}', [ExternalSeminarController::class, 'show'])->name('seminars.show');
        });
        Route::middleware('ability:seminars:write')->group(function () {
            Route::post('seminars', [ExternalSeminarController::class, 'store'])->name('seminars.store');
            Route::match(['put', 'patch'], 'seminars/{seminar:slug}', [ExternalSeminarController::class, 'update'])->name('seminars.update');
        });

        // Seminar Types
        Route::middleware('ability:seminar-types:read')->group(function () {
            Route::get('seminar-types', [ExternalSeminarTypeController::class, 'index'])->name('seminar-types.index');
            Route::get('seminar-types/{seminar_type}', [ExternalSeminarTypeController::class, 'show'])->name('seminar-types.show');
        });
        Route::middleware('ability:seminar-types:write')->group(function () {
            Route::post('seminar-types', [ExternalSeminarTypeController::class, 'store'])->name('seminar-types.store');
            Route::match(['put', 'patch'], 'seminar-types/{seminar_type}', [ExternalSeminarTypeController::class, 'update'])->name('seminar-types.update');
        });

        // Locations
        Route::middleware('ability:locations:read')->group(function () {
            Route::get('locations', [ExternalLocationController::class, 'index'])->name('locations.index');
            Route::get('locations/{location}', [ExternalLocationController::class, 'show'])->name('locations.show');
        });
        Route::middleware('ability:locations:write')->group(function () {
            Route::post('locations', [ExternalLocationController::class, 'store'])->name('locations.store');
            Route::match(['put', 'patch'], 'locations/{location}', [ExternalLocationController::class, 'update'])->name('locations.update');
        });

        // Users
        Route::middleware('ability:users:read')->group(function () {
            Route::get('users', [ExternalUserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [ExternalUserController::class, 'show'])->name('users.show');
        });
        Route::middleware('ability:users:write')->group(function () {
            Route::post('users', [ExternalUserController::class, 'store'])->name('users.store');
            Route::match(['put', 'patch'], 'users/{user}', [ExternalUserController::class, 'update'])->name('users.update');
        });

        // Workshops
        Route::middleware('ability:workshops:read')->group(function () {
            Route::get('workshops', [ExternalWorkshopController::class, 'index'])->name('workshops.index');
            Route::get('workshops/{workshop:slug}', [ExternalWorkshopController::class, 'show'])->name('workshops.show');
        });
        Route::middleware('ability:workshops:write')->group(function () {
            Route::post('workshops', [ExternalWorkshopController::class, 'store'])->name('workshops.store');
            Route::match(['put', 'patch'], 'workshops/{workshop:slug}', [ExternalWorkshopController::class, 'update'])->name('workshops.update');
        });

        // Speaker Data
        Route::get('users/{user}/speaker-data', [ExternalSpeakerDataController::class, 'show'])
            ->middleware('ability:speaker-data:read')
            ->name('users.speaker-data.show');
        Route::put('users/{user}/speaker-data', [ExternalSpeakerDataController::class, 'update'])
            ->middleware('ability:speaker-data:write')
            ->name('users.speaker-data.update');
    });
