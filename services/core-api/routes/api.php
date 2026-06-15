<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\PrivacyRequestController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — BAYT ALMOSWER Core. PRD §21 (API-First).
| المصادقة عبر Sanctum؛ المسارات المحمية تضبط سياق المؤسسة (tenant)
| وتفرض الصلاحيات على الخادم (permission:*). ADR-003, PRD §22.
*/

Route::prefix('v1')->group(function () {
    // عام
    Route::post('auth/login', [AuthController::class, 'login']);

    // محمي: مصادقة (سياق المؤسسة يُضبط مبكرًا عبر SetTenant في مجموعة api)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // الأشخاص (Person 360)
        Route::get('persons', [PersonController::class, 'index'])
            ->middleware('permission:persons.view');
        Route::get('persons/{person}', [PersonController::class, 'show'])
            ->middleware('permission:persons.view');
        Route::post('persons', [PersonController::class, 'store'])
            ->middleware('permission:persons.manage');
        Route::get('persons/{person}/activity', [PersonController::class, 'activity'])
            ->middleware('permission:audit.view');
        Route::get('persons/{person}/national-id', [PersonController::class, 'revealNationalId'])
            ->middleware('permission:persons.viewSensitive');

        // الموافقات (Consent) — PRD §19.4
        Route::middleware('permission:consents.manage')->group(function () {
            Route::get('persons/{person}/consents', [ConsentController::class, 'index']);
            Route::post('persons/{person}/consents', [ConsentController::class, 'store']);
            Route::post('consents/{consent}/withdraw', [ConsentController::class, 'withdraw']);
        });

        // طلبات حقوق صاحب البيانات (PDPL) — PRD §19.5
        Route::middleware('permission:privacy.handle')->group(function () {
            Route::get('privacy-requests', [PrivacyRequestController::class, 'index']);
            Route::post('privacy-requests', [PrivacyRequestController::class, 'store']);
            Route::get('privacy-requests/{privacyRequest}', [PrivacyRequestController::class, 'show']);
            Route::patch('privacy-requests/{privacyRequest}', [PrivacyRequestController::class, 'update']);
        });
    });
});
