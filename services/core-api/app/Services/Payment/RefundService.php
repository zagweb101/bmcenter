<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة الاسترداد. PRD §17, §27 (لا استرداد زائد).
 */
class RefundService
{
    public function refund(Payment $payment, string $amount, ?string $reason, ?int $userId): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $userId) {
            // قفل الدفعة لتسلسل قرارات الاسترداد المتزامنة.
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $amount = bcadd($amount, '0', 2);
            if (bccomp($amount, '0', 2) !== 1) {
                throw ValidationException::withMessages(['amount' => ['المبلغ يجب أن يكون موجبًا.']]);
            }

            $refundable = $locked->refundable();
            if (bccomp($amount, $refundable, 2) === 1) {
                throw ValidationException::withMessages([
                    'amount' => ["مبلغ الاسترداد يتجاوز المتبقّي القابل للاسترداد ({$refundable})."],
                ]);
            }

            return Refund::create([
                'organization_id' => $locked->organization_id,
                'payment_id' => $locked->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'confirmed',
                'refunded_by_user_id' => $userId,
                'refunded_at' => now(),
            ]);
        });
    }
}
