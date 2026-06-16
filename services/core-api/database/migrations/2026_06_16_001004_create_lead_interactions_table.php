<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تفاعلات الـ Lead (Interactions). PRD §13.
 * الأنواع: call | whatsapp | sms | email | visit | form | social | note
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 16);
            $table->text('body')->nullable();
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['lead_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
    }
};
