<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC مفروض على الخادم. PRD §10, §22.
 * الأدوار في MVP-0: Super Admin, Executive Manager, Branch Manager,
 * CRM Agent, Registration Officer, Accountant, Compliance Reviewer.
 * لا صلاحية تعتمد على الواجهة فقط (PRD §6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key');               // super_admin, accountant, ...
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_system')->default(false); // أدوار النظام لا تُحذف
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();     // مثل: persons.view, invoices.issue
            $table->string('name_ar');
            $table->string('group')->nullable(); // تجميع للعرض
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            // نطاق الفرع: قد يُقيَّد الدور بفرع محدد (PRD §10)
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
