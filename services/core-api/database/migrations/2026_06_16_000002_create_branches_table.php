<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الفرع (Branch) — كيان مرتبط بالموقع داخل المؤسسة.
 * PRD §12: الكيانات المرتبطة بالموقع تحمل branch_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('code')->nullable();

            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('building_number', 8)->nullable();
            $table->string('postal_code', 8)->nullable();
            $table->string('phone', 32)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
