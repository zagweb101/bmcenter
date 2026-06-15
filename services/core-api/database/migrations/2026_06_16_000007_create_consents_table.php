<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل الموافقات (Consent Records). PRD §19.4.
 * يدعم: نص، إصدار، غرض، قناة، تاريخ منح/سحب، ودليل.
 * أغراض منفصلة: privacy | marketing | whatsapp | media_publishing | community.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();

            $table->string('purpose', 32);          // privacy | marketing | whatsapp | media_publishing | community
            $table->string('text_version');         // إصدار نص الموافقة المعروض
            $table->text('text_snapshot')->nullable(); // نص الموافقة وقت المنح (دليل)
            $table->string('channel', 32)->nullable(); // web_form | crm_agent | landing | ...

            $table->string('status', 16)->default('granted'); // granted | withdrawn
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            // دليل (PRD §19.4): IP/UserAgent/مرجع المصدر
            $table->string('evidence_ip', 64)->nullable();
            $table->string('evidence_user_agent')->nullable();
            $table->string('evidence_reference')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'person_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
