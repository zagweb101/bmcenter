<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل التدقيق (Audit Logs). PRD §6, §11, §22.
 * كل وصول لبيانات حساسة يُسجَّل؛ الدمج وتغيير الصلاحيات يُسجَّل.
 * append-only منطقيًا (لا تعديل/حذف).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');                  // created | updated | deleted | viewed_sensitive | merged | ...
            $table->nullableMorphs('subject');         // subject_type/id للكيان المتأثر

            // لقطة قبل/بعد (مع إخفاء البيانات الشخصية في القنوات الأخرى — §22)
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('context')->nullable();      // route, reason, ...

            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'action']);
            // فهرس (subject_type, subject_id) يُنشأ تلقائيًا عبر nullableMorphs أعلاه
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
