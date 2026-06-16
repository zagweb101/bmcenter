<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CohortController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PaymentController;
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
        Route::post('persons/match', [PersonController::class, 'matchCandidates'])
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

        // العملاء المحتملون (Leads / CRM) — PRD §13
        Route::get('leads', [LeadController::class, 'index'])->middleware('permission:leads.view');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view');
        Route::post('leads', [LeadController::class, 'store'])->middleware('permission:leads.manage');
        Route::patch('leads/{lead}/transition', [LeadController::class, 'transition'])->middleware('permission:leads.manage');
        Route::post('leads/{lead}/interactions', [LeadController::class, 'addInteraction'])->middleware('permission:leads.manage');
        Route::patch('leads/{lead}/assign', [LeadController::class, 'assign'])->middleware('permission:leads.assign');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->middleware('permission:leads.manage');

        // الدورات والمجموعات (Courses / Cohorts) — PRD §14
        Route::get('courses', [CourseController::class, 'index'])->middleware('permission:courses.view');
        Route::post('courses', [CourseController::class, 'store'])->middleware('permission:courses.manage');
        Route::get('cohorts', [CohortController::class, 'index'])->middleware('permission:courses.view');
        Route::post('cohorts', [CohortController::class, 'store'])->middleware('permission:courses.manage');

        // التسجيل (Enrollment) — PRD §14, §15
        Route::get('enrollments', [EnrollmentController::class, 'index'])->middleware('permission:enrollments.view');
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show'])->middleware('permission:enrollments.view');
        Route::post('enrollments', [EnrollmentController::class, 'store'])->middleware('permission:enrollments.manage');
        Route::patch('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->middleware('permission:enrollments.manage');
        Route::post('enrollments/{enrollment}/transfer', [EnrollmentController::class, 'transfer'])->middleware('permission:enrollments.manage');

        // الفواتير (Invoices) — PRD §8.3, §16
        Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view');
        Route::get('invoices/{invoice}/balance', [InvoiceController::class, 'balance'])->middleware('permission:invoices.view');
        Route::post('enrollments/{enrollment}/invoice', [InvoiceController::class, 'storeFromEnrollment'])->middleware('permission:invoices.issue');
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->middleware('permission:invoices.issue');
        Route::post('invoices/{invoice}/credit-note', [InvoiceController::class, 'creditNote'])->middleware('permission:invoices.issue');
        Route::post('invoices/{invoice}/debit-note', [InvoiceController::class, 'debitNote'])->middleware('permission:invoices.issue');

        // المدفوعات والسندات (Payments / Receipts) — PRD §17, §18
        Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payments.view');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view');
        Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:payments.manage');

        // اعتماد الطلبات (Approvals) — PRD §15
        Route::middleware('permission:approvals.review')->group(function () {
            Route::get('approvals', [ApprovalController::class, 'index']);
            Route::patch('approvals/{approval}/approve', [ApprovalController::class, 'approve']);
            Route::patch('approvals/{approval}/reject', [ApprovalController::class, 'reject']);
        });
    });
});
