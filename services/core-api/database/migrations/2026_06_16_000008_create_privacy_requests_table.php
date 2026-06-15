<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طلبات حقوق صاحب البيانات (PDPL). PRD §19.5.
 * النوع: access | copy | rectify | erase | withdraw_consent | inform
 * Workflow موثّق مع التحقق من الهوية والسبب والنتيجة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();

            $table->string('type', 32);                 // access | copy | rectify | erase | withdraw_consent | inform
            $table->string('status', 24)->default('received'); // received | verifying | in_progress | fulfilled | rejected
            $table->text('subject_reason')->nullable();

            $table->boolean('identity_verified')->default(false);
            $table->string('verification_method')->nullable();

            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();    // المدة النظامية للرد
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
    }
};
