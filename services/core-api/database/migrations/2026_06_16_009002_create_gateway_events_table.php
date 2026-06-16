<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أحداث الـ Webhook (Idempotency). PRD §18, ADR-005.
 * event_id فريد لمنع المعالجة المكررة (Idempotent) ومنع Replay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('event_id');
            $table->string('type', 64)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_events');
    }
};
