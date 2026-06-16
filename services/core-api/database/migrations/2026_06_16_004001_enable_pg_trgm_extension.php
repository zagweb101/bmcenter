<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تفعيل pg_trgm للمطابقة الاحتمالية وبحث التشابه. PRD §11, §20.
 * فهرس GIN على full_name لتسريع similarity().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS persons_full_name_trgm_idx ON persons USING gin (full_name gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS persons_full_name_trgm_idx');
        // لا نُسقط الامتداد (قد تعتمد عليه كيانات أخرى).
    }
};
