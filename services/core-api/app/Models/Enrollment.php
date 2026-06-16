<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** التسجيل (Enrollment). PRD §14, §15. */
class Enrollment extends Model
{
    use BelongsToOrganization, Auditable, SoftDeletes;

    /** الحالات التي تشغل مقعدًا فعليًا في المجموعة (لحساب السعة). */
    public const SEAT_OCCUPYING = [
        'pending_approval', 'pending_invoice', 'pending_payment', 'confirmed', 'completed',
    ];

    protected $fillable = [
        'organization_id', 'cohort_id', 'person_id', 'status',
        'price_snapshot', 'tax_rate_snapshot', 'discount_amount_snapshot',
        'tax_amount_snapshot', 'total_snapshot', 'discount_reason', 'enrolled_at',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'tax_rate_snapshot' => 'decimal:2',
        'discount_amount_snapshot' => 'decimal:2',
        'tax_amount_snapshot' => 'decimal:2',
        'total_snapshot' => 'decimal:2',
        'enrolled_at' => 'datetime',
    ];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
