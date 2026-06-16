<?php

namespace App\Providers;

use App\Services\Audit\AuditLogger;
use App\Services\Compliance\Zatca\Contracts\ZatcaClient;
use App\Services\Compliance\Zatca\ProductionZatcaClient;
use App\Services\Compliance\Zatca\SimulationZatcaClient;
use App\Services\Payment\Gateway\Contracts\PaymentGateway;
use App\Services\Payment\Gateway\ProductionGateway;
use App\Services\Payment\Gateway\SimulationGateway;
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

        // عميل ZATCA حسب الإعداد (ADR-004) — simulation الآن، production عند الربط
        $this->app->bind(ZatcaClient::class, function () {
            return config('zatca.driver') === 'production'
                ? new ProductionZatcaClient()
                : new SimulationZatcaClient();
        });

        // بوابة الدفع حسب الإعداد (ADR-004) — simulation الآن، production عند الربط
        $this->app->bind(PaymentGateway::class, function () {
            return config('payments.driver') === 'production'
                ? new ProductionGateway()
                : new SimulationGateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
