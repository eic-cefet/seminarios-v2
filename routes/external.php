<?php

use App\Http\Controllers\External\ExternalLocationController;
use App\Http\Controllers\External\ExternalSeminarController;
use App\Http\Controllers\External\ExternalSeminarTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('external/v1')
    ->group(function () {
        Route::apiResource('seminars', ExternalSeminarController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::apiResource('seminar-types', ExternalSeminarTypeController::class)
            ->only(['index', 'show', 'store', 'update']);

        Route::apiResource('locations', ExternalLocationController::class)
            ->only(['index', 'show', 'store', 'update']);
    });
