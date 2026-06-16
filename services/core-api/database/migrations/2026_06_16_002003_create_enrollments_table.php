<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * التسجيل (Enrollment) — علاقة الطالب بالمجموعة مع Snapshot. PRD §14, §15.
 * الحالات: draft → pending_approval → pending_invoice → pending_payment
 *          → confirmed / waitlisted / transferred / cancelled / completed
 * Snapshot للسعر/الضريبة/الخصم وقت التسجيل (لا يتأثر بتغيّر سعر المجموعة لاحقًا).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();

            $table->string('status', 24)->default('pending_invoice');

            // Snapshot وقت التسجيل (ADR-006 Decimal)
            $table->decimal('price_snapshot', 15, 2)->default(0);
            $table->decimal('tax_rate_snapshot', 5, 2)->default(0);
            $table->decimal('discount_amount_snapshot', 15, 2)->default(0);
            $table->decimal('tax_amount_snapshot', 15, 2)->default(0);
            $table->decimal('total_snapshot', 15, 2)->default(0); // المستحق بعد الخصم + الضريبة
            $table->string('discount_reason')->nullable();

            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // طالب واحد لا يُسجَّل مرتين في نفس المجموعة
            $table->unique(['cohort_id', 'person_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
