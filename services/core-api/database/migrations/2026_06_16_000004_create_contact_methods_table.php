<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وسائل التواصل (Contact Methods) — متعددة لكل شخص. PRD §8.1, §11.
 * type: phone | whatsapp | email | other
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();

            $table->string('type', 16);                 // phone | whatsapp | email | other
            $table->string('value');                    // العرض الأصلي
            $table->string('value_normalized')->index(); // E.164 أو lowercase email للمطابقة
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'type', 'value_normalized']);
            $table->index(['person_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_methods');
    }
};
