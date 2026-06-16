<?php

namespace App\Services\Payment;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة المدفوعات — المسار اليدوي المنضبط. PRD §17, §18, §27.
 * ضمانات النزاهة:
 *  - كل دفعة مؤكدة تُنتج سندًا (Receipt).
 *  - لا تخصيص زائد: مجموع التخصيصات ≤ مبلغ الدفعة، وكل تخصيص ≤ متبقي فاتورته.
 *  - Decimal (bcmath، ADR-006).
 */
class PaymentService
{
    /**
     * @param array $data method, amount, person_id?, reference?, allocations: [{invoice_id, amount}]
     */
    public function recordManual(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $amount = bcadd((string) $data['amount'], '0', 2);
            if (bccomp($amount, '0', 2) !== 1) {
                throw ValidationException::withMessages(['amount' => ['المبلغ يجب أن يكون أكبر من صفر.']]);
            }

            $payment = Payment::create([
                'organization_id' => $data['organization_id'] ?? app(\App\Support\Tenancy\Tenancy::class)->id(),
                'person_id' => $data['person_id'] ?? null,
                'method' => $data['method'],
                'amount' => $amount,
                'currency' => 'SAR',
                'status' => 'confirmed',
                'reference' => $data['reference'] ?? null,
                'gateway_txn_ref' => $data['gateway_txn_ref'] ?? null,
                'paid_at' => now(),
            ]);

            $allocatedSum = '0';
            foreach (($data['allocations'] ?? []) as $alloc) {
                $invoice = Invoice::whereKey($alloc['invoice_id'])->lockForUpdate()->first();
                if (! $invoice) {
                    throw ValidationException::withMessages(['allocations' => ['فاتورة غير صالحة ضمن المؤسسة.']]);
                }

                $allocAmount = bcadd((string) $alloc['amount'], '0', 2);
                if (bccomp($allocAmount, '0', 2) !== 1) {
                    throw ValidationException::withMessages(['allocations' => ['مبلغ التخصيص يجب أن يكون موجبًا.']]);
                }

                // لا تخصيص يتجاوز متبقي الفاتورة (§27).
                $outstanding = $invoice->outstanding();
                if (bccomp($allocAmount, $outstanding, 2) === 1) {
                    throw ValidationException::withMessages([
                        'allocations' => ["التخصيص يتجاوز متبقي الفاتورة #{$invoice->id} ({$outstanding})."],
                    ]);
                }

                $allocatedSum = bcadd($allocatedSum, $allocAmount, 2);
                // لا يتجاوز مجموع التخصيصات مبلغ الدفعة (§27).
                if (bccomp($allocatedSum, $amount, 2) === 1) {
                    throw ValidationException::withMessages([
                        'allocations' => ['مجموع التخصيصات يتجاوز مبلغ الدفعة.'],
                    ]);
                }

                $payment->allocations()->create([
                    'organization_id' => $payment->organization_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocAmount,
                ]);

                // عند سداد الفاتورة بالكامل → تأكيد تسجيلها المرتبط (§14).
                if (bccomp($invoice->outstanding(), '0', 2) === 0 && $invoice->enrollment_id) {
                    Enrollment::where('id', $invoice->enrollment_id)
                        ->where('status', 'pending_payment')
                        ->update(['status' => 'confirmed']);
                }
            }

            // سند لكل دفعة مؤكدة (§17, §27).
            Receipt::create([
                'organization_id' => $payment->organization_id,
                'payment_id' => $payment->id,
                'receipt_number' => 'REC-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'amount' => $payment->amount,
                'issued_at' => now(),
            ]);

            return $payment->load('allocations', 'receipt');
        });
    }
}
