<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PersonController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — BAYT ALMOSWER Core. PRD §21 (API-First).
| المصادقة عبر Sanctum؛ المسارات المحمية تضبط سياق المؤسسة (tenant)
| وتفرض الصلاحيات على الخادم (permission:*). ADR-003, PRD §22.
*/

Route::prefix('v1')->group(function () {
    // عام
    Route::post('auth/login', [AuthController::class, 'login']);

    // محمي: مصادقة + سياق المؤسسة
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // الأشخاص (Person 360)
        Route::get('persons', [PersonController::class, 'index'])
            ->middleware('permission:persons.view');
        Route::get('persons/{person}', [PersonController::class, 'show'])
            ->middleware('permission:persons.view');
        Route::post('persons', [PersonController::class, 'store'])
            ->middleware('permission:persons.manage');
    });
});
