<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الإقفال اليومي (Cash Closing). PRD §8.3.
 * لقطة محصّلات اليوم حسب طريقة الدفع، تُقفَل لمنع التعديل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('closing_date');
            $table->jsonb('totals_by_method')->nullable();   // {cash: "..", bank_transfer: ".."}
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedInteger('payments_count')->default(0);
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
    }
};
