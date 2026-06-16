<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مهام/متابعات الـ Lead (Follow-ups / Tasks). PRD §13 (المهتم له موعد متابعة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->timestamp('due_at')->nullable();
            $table->string('status', 16)->default('open'); // open | done | cancelled
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tasks');
    }
};
