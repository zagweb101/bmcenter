<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** تفاعل مع Lead. PRD §13. */
class LeadInteraction extends Model
{
    use BelongsToOrganization;

    public const TYPES = [
        'call', 'whatsapp', 'sms', 'email', 'visit', 'form', 'social', 'note',
    ];

    protected $fillable = [
        'organization_id', 'lead_id', 'user_id', 'type', 'body', 'occurred_at',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
