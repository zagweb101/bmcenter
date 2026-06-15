<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بنود الفاتورة (Invoice Lines). PRD §16.3, §17.
 * Decimal لكل المبالغ (ADR-006).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->string('tax_category', 8)->default('S');   // S=Standard, Z=Zero, E=Exempt
            $table->decimal('tax_rate', 5, 2)->default(15.00); // قابل للتهيئة (§ملحق أ)
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('line_total_excluding_tax', 15, 2)->default(0);
            $table->decimal('line_total_including_tax', 15, 2)->default(0);

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
