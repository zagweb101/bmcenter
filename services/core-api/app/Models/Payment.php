<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** الدفعة (Payment). PRD §17, §18. لا حذف للمؤكدة. */
class Payment extends Model
{
    use BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'person_id', 'method', 'amount', 'currency',
        'status', 'reference', 'paid_at', 'gateway_txn_ref',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
