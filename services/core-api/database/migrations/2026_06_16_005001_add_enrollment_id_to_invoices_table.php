<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط الفاتورة بالتسجيل المصدر. PRD §8.3 (Enrollment → Invoice Draft).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->nullable()->after('buyer_person_id')
                  ->constrained('enrollments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrollment_id');
        });
    }
};
