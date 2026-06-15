<?php

namespace App\Providers;

use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\Tenancy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // سياق المؤسسة الحالية — singleton يُضبط من Middleware/Auth (PRD §12, ADR-003)
        $this->app->singleton(Tenancy::class);

        // خدمة سجل التدقيق (PRD §6, §22)
        $this->app->singleton(AuditLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
