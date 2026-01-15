<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Certificate routes (before SPA catch-all)
Route::get('/certificado/{code}', [CertificateController::class, 'show'])->name('certificate.show');
Route::get('/certificado/{code}/jpg', [CertificateController::class, 'showJpg'])->name('certificate.show.jpg');

// OAuth routes
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.callback');

// System SPA (public/student)
Route::get('/{any?}', fn () => view('system'))
    ->where('any', '^(?!admin|api|sanctum|certificado|auth/(?:google|github)(?:/callback)?).*$')
    ->name('system');

// Admin SPA
Route::get('/admin/{any?}', fn () => view('admin'))
    ->where('any', '.*')
    ->middleware(['auth'])
    ->name('admin');
