<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** طلب اعتماد (Approval Request). PRD §15. */
class ApprovalRequest extends Model
{
    use BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'approvable_type', 'approvable_id', 'type', 'amount',
        'reason', 'status', 'requested_by_user_id', 'reviewed_by_user_id',
        'reviewed_at', 'decision_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
