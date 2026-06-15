<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SetTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // PRD §12, ADR-003 — يضبط سياق المؤسسة مبكرًا، قبل ربط النماذج (SubstituteBindings)
        // ليُطبَّق OrganizationScope على الـ route-model binding بشكل صحيح.
        $middleware->api(prepend: [SetTenant::class]);

        // PRD §22 — يُفرض على المسارات المحمية
        $middleware->alias([
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
