<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الفرص (Opportunities). PRD §8.2, §13.
 * فرصة تسجيل محتملة مرتبطة بـ Lead، باهتمام بدورة وقيمة تقديرية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();

            $table->string('title');
            $table->string('course_interest')->nullable();
            $table->decimal('estimated_value', 15, 2)->default(0); // ADR-006 Decimal
            $table->string('stage', 24)->default('open');
            $table->string('status', 16)->default('open'); // open | won | lost
            $table->date('expected_close_on')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
