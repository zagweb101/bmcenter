<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * حدث Webhook (Idempotency). PRD §18, ADR-005.
 * عام عبر المؤسسات (يُعالَج قبل تحديد السياق) — لا BelongsToOrganization.
 */
class GatewayEvent extends Model
{
    protected $fillable = ['provider', 'event_id', 'type', 'payload', 'processed_at'];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
