<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** تخصيص دفعة على فاتورة. PRD §17. */
class PaymentAllocation extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'payment_id', 'invoice_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
