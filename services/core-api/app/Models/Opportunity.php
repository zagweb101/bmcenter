<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** فرصة تسجيل. PRD §13. */
class Opportunity extends Model
{
    use BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'lead_id', 'person_id', 'title', 'course_interest',
        'estimated_value', 'stage', 'status', 'expected_close_on',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_close_on' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
