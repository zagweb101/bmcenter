<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** الإقفال اليومي. PRD §8.3. */
class CashClosing extends Model
{
    use BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'closing_date', 'totals_by_method', 'total_amount',
        'payments_count', 'closed_by_user_id', 'closed_at',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'totals_by_method' => 'array',
        'total_amount' => 'decimal:2',
        'closed_at' => 'datetime',
    ];
}
