<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المؤسسة (Organization) — الكيان الجذري للـ Tenant.
 * PRD §12 / ADR-003: كل كيان تشغيلي يحمل organization_id.
 * في MVP-0 مؤسسة واحدة، لكن النموذج Tenant-Aware من اليوم الأول.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();

            // بيانات ضريبية/تجارية (PRD §33 — بوابة بدء التنفيذ)
            $table->string('vat_number', 15)->nullable();          // الرقم الضريبي
            $table->string('commercial_registration')->nullable(); // السجل التجاري
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();

            // العنوان الوطني (مطلوب لبيانات البائع في ZATCA)
            $table->string('country_code', 2)->default('SA');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('building_number', 8)->nullable();
            $table->string('postal_code', 8)->nullable();

            $table->string('default_currency', 3)->default('SAR'); // ADR-006
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
