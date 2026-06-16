<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حد الخصم لكل دور. PRD §15 (لكل دور حد خصم؛ ما يتجاوزه يحتاج اعتمادًا).
 * NULL = غير محدود (مثل super_admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->decimal('max_discount_amount', 15, 2)->nullable()->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('max_discount_amount');
        });
    }
};
