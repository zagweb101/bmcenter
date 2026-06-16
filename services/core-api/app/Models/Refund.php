<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** استرداد (Refund). PRD §17, §27. */
class Refund extends Model
{
    use BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'payment_id', 'amount', 'reason',
        'status', 'refunded_by_user_id', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
