<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المدفوعات (Payments). PRD §17, §18.
 * الأموال المستلمة/المؤكدة. لا حذف للدفعة المؤكدة (§17).
 * المسار اليدوي المنضبط (نقد/تحويل/SADAD) جاهز كخطة بديلة (§18).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();

            $table->string('method', 24);          // cash | bank_transfer | sadad | gateway
            $table->decimal('amount', 15, 2);       // ADR-006
            $table->string('currency', 3)->default('SAR');
            $table->string('status', 16)->default('confirmed'); // confirmed | pending | failed
            $table->string('reference')->nullable();           // مرجع التحويل/السداد
            $table->timestamp('paid_at')->nullable();

            // ربط بمعاملة بوابة لاحقًا (المصدر الموثوق هو الـWebhook — ADR-005)
            $table->string('gateway_txn_ref')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
