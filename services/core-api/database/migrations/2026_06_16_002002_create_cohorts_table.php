<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المجموعة (Cohort) — تنفيذ محدد لدورة بفرع/مدرب/سعة/سعر/فترة. PRD §14.
 * الحالات: draft → enrollment_open → minimum_reached → confirmed
 *          → enrollment_closed → in_progress → completed/cancelled → archived
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->unsignedInteger('capacity')->default(0);     // 0 = غير محدد
            $table->decimal('price', 15, 2)->default(0);          // ADR-006
            $table->decimal('tax_rate', 5, 2)->default(15.00);    // قابل للتهيئة
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();

            $table->string('status', 24)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
