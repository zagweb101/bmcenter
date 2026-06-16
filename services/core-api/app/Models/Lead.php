<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * العميل المحتمل (Lead). PRD §13.
 */
class Lead extends Model
{
    use BelongsToOrganization, Auditable, SoftDeletes;

    /** مراحل Pipeline الافتراضية (PRD §13). */
    public const STAGES = [
        'new', 'assigned', 'contacted', 'qualified', 'interested',
        'payment_pending', 'enrolled', 'nurturing', 'lost',
    ];

    /** مراحل لا تُضبط يدويًا (تنتج من عمليات أخرى). */
    public const SYSTEM_STAGES = ['enrolled'];

    protected $fillable = [
        'organization_id', 'branch_id', 'person_id', 'lead_source_id',
        'campaign_id', 'owner_user_id', 'full_name', 'phone_e164', 'email',
        'stage', 'status', 'lost_reason', 'next_follow_up_at', 'notes',
    ];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LeadTask::class);
    }
}
