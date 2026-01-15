<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegistrationController;
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

// Auth
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::get('/auth/me', [AuthController::class, 'me']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// Seminars
Route::get('/seminars', [SeminarController::class, 'index']);
Route::get('/seminars/upcoming', [SeminarController::class, 'upcoming']);
Route::get('/seminars/{slug}', [SeminarController::class, 'show']);

// Seminar Registration
Route::get('/seminars/{slug}/registration', [RegistrationController::class, 'status']);
Route::post('/seminars/{slug}/register', [RegistrationController::class, 'register']);
Route::delete('/seminars/{slug}/register', [RegistrationController::class, 'unregister']);

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

// Courses
Route::get('/courses', [CourseController::class, 'index']);

// Stats
Route::get('/stats', [StatsController::class, 'index']);

// Presence Links (QR Code)
Route::get('/presence/{uuid}', [PresenceController::class, 'show']);
Route::post('/presence/{uuid}/register', [PresenceController::class, 'register']);

// Auth
Route::post('/auth/exchange', [\App\Http\Controllers\SocialAuthController::class, 'exchange']);

// Profile (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/student-data', [ProfileController::class, 'updateStudentData']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::get('/profile/registrations', [ProfileController::class, 'registrations']);
    Route::get('/profile/certificates', [ProfileController::class, 'certificates']);
});
