<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugReportController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DataPrivacyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\ProfileAlertPreferenceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfileRatingController;
use App\Http\Controllers\Api\ProfileRegistrationController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\SeminarController;
use App\Http\Controllers\Api\SeminarTypeController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TwoFactorChallengeController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\WorkshopController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Auth (tighter, purpose-specific throttles)
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/auth/exchange', [SocialAuthController::class, 'exchange'])->middleware('throttle:5,1');
Route::post('/auth/two-factor-challenge', [TwoFactorChallengeController::class, '__invoke'])->middleware('throttle:5,1');

// Bug Report (tightest throttle)
Route::post('/bug-report', [BugReportController::class, 'store'])->middleware('throttle:3,1');

// Consent (accessible to both authenticated and anonymous users)
Route::post('/consents', [ConsentController::class, 'store'])->middleware('throttle:5,1');

// General public endpoints (120/min per user or IP)
Route::middleware('throttle:public')->group(function () {
    // Auth session
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Seminars
    Route::get('/seminars', [SeminarController::class, 'index']);
    Route::get('/seminars/upcoming', [SeminarController::class, 'upcoming']);
    Route::get('/seminars/{slug}', [SeminarController::class, 'show']);

    // Seminar Registration (status is public, register/unregister require auth)
    Route::get('/seminars/{slug}/registration', [RegistrationController::class, 'status']);

    // Subjects
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject:slug}', [SubjectController::class, 'show']);
    Route::get('/subjects/{subject:slug}/seminars', [SeminarController::class, 'bySubject']);

    // Workshops
    Route::get('/workshops', [WorkshopController::class, 'index']);
    Route::get('/workshops/{workshop:slug}', [WorkshopController::class, 'show']);
    Route::get('/workshops/{workshop:slug}/seminars', [WorkshopController::class, 'seminars']);

    // Seminar Types
    Route::get('/seminar-types', [SeminarTypeController::class, 'index']);

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);

    // Stats
    Route::get('/stats', [StatsController::class, 'index']);

    // Presence Links (QR Code)
    Route::get('/presence/{uuid}', [PresenceController::class, 'show']);
    Route::post('/presence/{uuid}/register', [PresenceController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Consents (listing requires auth)
    Route::get('/consents', [ConsentController::class, 'index']);

    // Seminar Registration
    Route::post('/seminars/{slug}/register', [RegistrationController::class, 'register']);
    Route::delete('/seminars/{slug}/register', [RegistrationController::class, 'unregister']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/student-data', [ProfileController::class, 'updateStudentData']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Profile - Alert Preferences
    Route::get('/profile/alert-preferences', [ProfileAlertPreferenceController::class, 'show']);
    Route::put('/profile/alert-preferences', [ProfileAlertPreferenceController::class, 'update']);

    // Profile - Registrations & Certificates
    Route::get('/profile/registrations', [ProfileRegistrationController::class, 'registrations']);
    Route::get('/profile/certificates', [ProfileRegistrationController::class, 'certificates']);

    // Profile - Evaluations & Ratings
    Route::get('/profile/pending-evaluations', [ProfileRatingController::class, 'pendingEvaluations']);
    Route::post('/profile/ratings/{seminar}', [ProfileRatingController::class, 'submitRating']);

    // Profile - Data Export (LGPD)
    Route::get('/profile/data-export', [DataPrivacyController::class, 'indexExports']);
    Route::post('/profile/data-export', [DataPrivacyController::class, 'requestExport'])
        ->middleware('throttle:5,60');

    // Profile - Account Deletion (LGPD)
    Route::post('/profile/delete-request', [DataPrivacyController::class, 'requestDeletion'])
        ->middleware('throttle:3,1');
    Route::post('/profile/delete-confirm', [DataPrivacyController::class, 'confirmDeletion'])
        ->middleware('throttle:3,1');
    Route::post('/profile/delete-cancel', [DataPrivacyController::class, 'cancelDeletion'])
        ->middleware('throttle:3,1');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Profile - Two-Factor Authentication
    Route::prefix('profile/two-factor')->group(function () {
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
        Route::delete('/', [TwoFactorController::class, 'disable']);
        Route::get('/devices', [TwoFactorController::class, 'devices']);
        Route::delete('/devices/{device}', [TwoFactorController::class, 'revokeDevice']);
    });
});
