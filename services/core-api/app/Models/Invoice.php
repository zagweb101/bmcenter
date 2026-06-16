<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * الفاتورة (Invoice). تصميم في 0A، تكامل ZATCA في 0C. PRD §16, §17.
 * النزاهة: لا تُحذف الصادرة؛ التصحيح عبر إشعارات (يُفرض في الكود/0C).
 */
class Invoice extends Model
{
    use BelongsToOrganization, Auditable;

    // الحالات غير القابلة للتعديل (صادرة فعليًا) — PRD §16.5, §17.
    public const ISSUED_STATES = [
        'pending_clearance', 'pending_reporting', 'cleared', 'reported', 'archived',
    ];

    protected $fillable = [
        'organization_id', 'branch_id', 'egs_unit_id', 'buyer_person_id', 'enrollment_id',
        'uuid', 'document_number', 'invoice_type_code', 'transaction_type',
        'original_invoice_id', 'seller_snapshot', 'buyer_snapshot', 'currency',
        'issued_at', 'subtotal', 'discount_total', 'tax_total',
        'total_including_tax', 'tax_breakdown', 'status',
        // حقول ZATCA (تُملأ عبر الـ Adapter) — PRD §16.3
        'icv', 'pih', 'invoice_hash', 'qr_payload', 'cryptographic_stamp',
        'cleared_xml', 'submission_warnings', 'submission_errors',
    ];

    protected $casts = [
        'seller_snapshot' => 'array',
        'buyer_snapshot' => 'array',
        'tax_breakdown' => 'array',
        'submission_warnings' => 'array',
        'submission_errors' => 'array',
        'issued_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total_including_tax' => 'decimal:2',
    ];

    /** الحقول المالية التي تُجمَّد بعد الإصدار (PRD §17). */
    public const FROZEN_FIELDS = [
        'subtotal', 'discount_total', 'tax_total', 'total_including_tax',
        'tax_breakdown', 'buyer_person_id', 'enrollment_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->uuid ??= (string) Str::uuid();
        });

        // PRD §17, §27: لا تعديل للمبالغ بعد الإصدار؛ التصحيح عبر إشعار.
        static::updating(function (Invoice $invoice) {
            $wasIssued = in_array($invoice->getOriginal('status'), self::ISSUED_STATES, true)
                || $invoice->getOriginal('status') === 'issued';
            if (! $wasIssued) {
                return;
            }
            foreach (self::FROZEN_FIELDS as $field) {
                if ($invoice->isDirty($field)) {
                    throw new \DomainException('لا يجوز تعديل مبالغ فاتورة صادرة؛ استخدم إشعارًا دائنًا/مدينًا.');
                }
            }
        });

        // PRD §17: لا حذف لفاتورة صادرة.
        static::deleting(function (Invoice $invoice) {
            if ($invoice->isIssued() || $invoice->status === 'issued') {
                throw new \DomainException('لا يجوز حذف فاتورة صادرة.');
            }
        });
    }

    public function isIssuedManual(): bool
    {
        return $this->status === 'issued' || $this->isIssued();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'buyer_person_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isIssued(): bool
    {
        return in_array($this->status, self::ISSUED_STATES, true);
    }

    /** المبلغ المخصَّص (المدفوع) لهذه الفاتورة. */
    public function allocatedTotal(): string
    {
        return bcadd((string) $this->allocations()->sum('amount'), '0', 2);
    }

    /** الرصيد المتبقّي = الإجمالي − المخصَّص. PRD §17. */
    public function outstanding(): string
    {
        return bcsub((string) $this->total_including_tax, $this->allocatedTotal(), 2);
    }
}
