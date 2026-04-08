<?php

use App\Http\Controllers\External\ExternalLocationController;
use App\Http\Controllers\External\ExternalSeminarController;
use App\Http\Controllers\External\ExternalSeminarTypeController;
use App\Http\Controllers\External\ExternalSpeakerDataController;
use App\Http\Controllers\External\ExternalUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('external/v1')
    ->name('api.external.')
    ->group(function () {
        Route::apiResource('seminars', ExternalSeminarController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::apiResource('seminar-types', ExternalSeminarTypeController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::apiResource('locations', ExternalLocationController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::apiResource('users', ExternalUserController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::get('users/{user}/speaker-data', [ExternalSpeakerDataController::class, 'show'])
            ->name('users.speaker-data.show');
        Route::put('users/{user}/speaker-data', [ExternalSpeakerDataController::class, 'update'])
            ->name('users.speaker-data.update');
    });
