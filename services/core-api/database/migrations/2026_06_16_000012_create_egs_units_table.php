<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EGS Unit — وحدة توليد الفواتير الإلكترونية. PRD §16.4.
 * تضم المؤسسة والفرع والبيئة وحالة Onboarding وبيانات CSID.
 * المفاتيح الخاصة لا تُحفظ كنص صريح — تُدار في Secrets Vault (مرجع فقط هنا).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egs_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('environment', 16)->default('sandbox'); // sandbox | simulation | production
            $table->string('unit_serial')->nullable();             // المعرّف الفريد للوحدة
            $table->string('common_name')->nullable();

            $table->string('onboarding_status', 24)->default('pending'); // pending | csr_generated | compliance | production | active
            $table->string('compliance_request_id')->nullable();
            $table->string('production_request_id')->nullable();

            // مراجع الأسرار فقط — القيم الفعلية في Vault (PRD §16.4, §22)
            $table->string('private_key_ref')->nullable();
            $table->string('compliance_csid_ref')->nullable();
            $table->string('production_csid_ref')->nullable();

            // ICV/PIH chain — آخر hash للحفاظ على سلسلة الفواتير (لا تُكسر أبدًا — §16.6)
            $table->unsignedBigInteger('last_icv')->default(0);
            $table->string('last_invoice_hash')->nullable();

            $table->timestamps();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egs_units');
    }
};
