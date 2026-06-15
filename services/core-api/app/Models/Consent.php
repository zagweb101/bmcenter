<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل موافقة (Consent). PRD §19.4.
 */
class Consent extends Model
{
    use HasFactory, BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'person_id', 'purpose', 'text_version', 'text_snapshot',
        'channel', 'status', 'granted_at', 'withdrawn_at',
        'evidence_ip', 'evidence_user_agent', 'evidence_reference',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'granted' && $this->withdrawn_at === null;
    }
}
