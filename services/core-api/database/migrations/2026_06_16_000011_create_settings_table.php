<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الإعدادات (Settings) — مفتاح/قيمة على مستوى المؤسسة. PRD §8.1.
 * مثل: ضريبة 15% قابلة للتهيئة (تُفعَّل فعليًا في 0C)، سياسة عرض الأرقام، حدود الخصم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->jsonb('value')->nullable();
            $table->string('type', 32)->default('string'); // string | number | bool | json
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
