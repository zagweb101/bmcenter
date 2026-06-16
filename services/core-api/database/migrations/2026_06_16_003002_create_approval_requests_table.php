<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طلبات الاعتماد (Approval Requests). PRD §15.
 * polymorphic: مرتبط بكيان (مثل enrollment) ونوع (discount).
 * النتيجة تُحفظ مع المعتمِد والسبب (لا تنفيذ قبل الاعتماد).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->morphs('approvable');                 // approvable_type/id
            $table->string('type', 32);                   // discount | ...
            $table->decimal('amount', 15, 2)->default(0); // قيمة المطلوب اعتماده (مثل الخصم)
            $table->text('reason')->nullable();

            $table->string('status', 16)->default('pending'); // pending | approved | rejected
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
