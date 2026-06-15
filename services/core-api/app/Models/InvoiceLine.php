<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * بند فاتورة (Invoice Line). PRD §16.3, §17. Decimal (ADR-006).
 */
class InvoiceLine extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'invoice_id', 'description', 'quantity', 'unit_price',
        'discount_amount', 'tax_category', 'tax_rate', 'tax_amount',
        'line_total_excluding_tax', 'line_total_including_tax',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total_excluding_tax' => 'decimal:2',
        'line_total_including_tax' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
