<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** سند استلام (Receipt). PRD §17, §27. */
class Receipt extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'payment_id', 'receipt_number', 'amount', 'issued_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
