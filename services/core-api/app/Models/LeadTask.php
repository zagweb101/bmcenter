<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** مهمة/متابعة Lead. PRD §13. */
class LeadTask extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'lead_id', 'assignee_user_id',
        'title', 'due_at', 'status', 'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
