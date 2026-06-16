<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** نية دفع (Payment Intent). PRD §18. */
class PaymentIntent extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'person_id', 'invoice_id', 'provider',
        'reference', 'amount', 'currency', 'status', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];
}
