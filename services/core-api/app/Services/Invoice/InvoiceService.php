<?php

namespace App\Services\Invoice;

use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة الفواتير. PRD §8.3, §16, §17.
 * توليد فاتورة Draft من تسجيل بالاعتماد على Snapshot المحفوظ وقت التسجيل.
 * (التكامل الفعلي مع ZATCA — التخليص/الإبلاغ — يتم لاحقًا في مسار 0C بعد Onboarding
 * ومراجعة مستشار ضريبي؛ يبقى المسار اليدوي المنضبط جاهزًا — PRD §16, §18, §33.)
 */
class InvoiceService
{
    public function createDraftFromEnrollment(Enrollment $enrollment): Invoice
    {
        return DB::transaction(function () use ($enrollment) {
            // فاتورة واحدة فعّالة لكل تسجيل.
            $existing = Invoice::where('enrollment_id', $enrollment->id)
                ->where('status', '!=', 'rejected')
                ->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'enrollment_id' => ['توجد فاتورة لهذا التسجيل بالفعل.'],
                ]);
            }

            $cohort = $enrollment->cohort()->first();
            $student = $enrollment->student()->first();

            $taxable = bcsub((string) $enrollment->price_snapshot, (string) $enrollment->discount_amount_snapshot, 2);

            $invoice = Invoice::create([
                'organization_id' => $enrollment->organization_id,
                'branch_id' => $cohort?->branch_id,
                'buyer_person_id' => $enrollment->person_id,
                'enrollment_id' => $enrollment->id,
                'invoice_type_code' => '388',          // Tax Invoice
                'transaction_type' => 'simplified',     // B2C افتراضيًا (§16.2)
                'currency' => 'SAR',                    // ADR-006
                'buyer_snapshot' => [
                    'name' => $student?->full_name ?? $student?->first_name,
                    'phone' => $student?->phone_e164,
                ],
                'subtotal' => (string) $enrollment->price_snapshot,
                'discount_total' => (string) $enrollment->discount_amount_snapshot,
                'tax_total' => (string) $enrollment->tax_amount_snapshot,
                'total_including_tax' => (string) $enrollment->total_snapshot,
                'tax_breakdown' => [[
                    'category' => 'S',
                    'rate' => (string) $enrollment->tax_rate_snapshot,
                    'taxable' => $taxable,
                    'tax' => (string) $enrollment->tax_amount_snapshot,
                ]],
                'status' => 'draft',
            ]);

            // رقم مستند فريد (تسلسلي بحسب المعرّف) — يُستبدل بآلية ZATCA عند الربط.
            $invoice->update(['document_number' => 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT)]);

            $invoice->lines()->create([
                'organization_id' => $enrollment->organization_id,
                'description' => $cohort?->name ?? 'تسجيل دورة',
                'quantity' => '1',
                'unit_price' => (string) $enrollment->price_snapshot,
                'discount_amount' => (string) $enrollment->discount_amount_snapshot,
                'tax_category' => 'S',
                'tax_rate' => (string) $enrollment->tax_rate_snapshot,
                'tax_amount' => (string) $enrollment->tax_amount_snapshot,
                'line_total_excluding_tax' => $taxable,
                'line_total_including_tax' => (string) $enrollment->total_snapshot,
            ]);

            // مسار التسجيل: pending_invoice → pending_payment (§14).
            if ($enrollment->status === 'pending_invoice') {
                $enrollment->update(['status' => 'pending_payment']);
            }

            return $invoice;
        });
    }
}
