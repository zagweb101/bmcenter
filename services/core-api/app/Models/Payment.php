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

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** إجمالي المسترد من هذه الدفعة. */
    public function refundedTotal(): string
    {
        return bcadd((string) $this->refunds()->where('status', 'confirmed')->sum('amount'), '0', 2);
    }

    /** المبلغ القابل للاسترداد المتبقّي = المبلغ − المسترد. PRD §27. */
    public function refundable(): string
    {
        return bcsub((string) $this->amount, $this->refundedTotal(), 2);
    }
}
