<?php

use App\Http\Controllers\Api\SeminarController;
use App\Http\Controllers\Api\SeminarTypeController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\WorkshopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Seminars
Route::get('/seminars', [SeminarController::class, 'index']);
Route::get('/seminars/upcoming', [SeminarController::class, 'upcoming']);
Route::get('/seminars/{slug}', [SeminarController::class, 'show']);

// Subjects
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
Route::get('/subjects/{subject}/seminars', [SeminarController::class, 'bySubject']);

// Workshops
Route::get('/workshops', [WorkshopController::class, 'index']);
Route::get('/workshops/{workshop}', [WorkshopController::class, 'show']);
Route::get('/workshops/{workshop}/seminars', [WorkshopController::class, 'seminars']);

// Seminar Types
Route::get('/seminar-types', [SeminarTypeController::class, 'index']);

// Stats
Route::get('/stats', [StatsController::class, 'index']);

// Auth
Route::post('/auth/exchange', [\App\Http\Controllers\SocialAuthController::class, 'exchange']);
