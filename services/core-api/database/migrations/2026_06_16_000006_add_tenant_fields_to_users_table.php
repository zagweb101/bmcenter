<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط المستخدم بالمؤسسة/الفرع/الشخص. PRD §11, §12.
 * المستخدم وجهٌ من أوجه Person 360؛ لا يُكرَّر الشخص.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')
                  ->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('organization_id')
                  ->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->after('branch_id')
                  ->constrained('persons')->nullOnDelete();

            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('mfa_enabled')->default(false)->after('is_active'); // §22 MFA
            $table->timestamp('last_login_at')->nullable();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn(['is_active', 'mfa_enabled', 'last_login_at']);
        });
    }
};
