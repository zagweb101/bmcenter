<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * العميل المحتمل (Lead). PRD §13.
 * Pipeline: new → assigned → contacted → qualified → interested
 *           → payment_pending → enrolled → nurturing → lost
 * قواعد: كل Lead له Source وOwner؛ الإغلاق كمفقود يتطلب سببًا؛
 * تغيير الموظف لا يفقد السجل السابق؛ مرحلة enrolled تنتج من Enrollment صالح.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            // قد يُربط بشخص موحّد (Person 360) أو يبقى Lead خامًا حتى المطابقة
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();

            // لقطة تواصل سريعة (قبل/بدون ربط بشخص)
            $table->string('full_name')->nullable();
            $table->string('phone_e164', 24)->nullable();
            $table->string('email')->nullable();

            $table->string('stage', 24)->default('new');
            $table->string('status', 16)->default('open'); // open | converted | lost
            $table->string('lost_reason')->nullable();      // إلزامي عند الإغلاق كمفقود

            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'stage']);
            $table->index(['organization_id', 'owner_user_id']);
            $table->index(['organization_id', 'phone_e164']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
