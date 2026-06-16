<?php

namespace App\Services\Enrollment;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة التسجيل. PRD §14, §15, §27.
 * - فحص السعة داخل transaction مع قفل الصف (lockForUpdate) لمنع تجاوز السعة (هدف §27 = 0).
 * - حفظ Snapshot للسعر/الخصم/الضريبة وقت التسجيل (Decimal — ADR-006، bcmath).
 * - عند امتلاء السعة → قائمة انتظار (waitlisted) بدل المنع.
 */
class EnrollmentService
{
    public function enroll(Cohort $cohort, Person $person, float|string $discount = 0, ?string $discountReason = null): Enrollment
    {
        return DB::transaction(function () use ($cohort, $person, $discount, $discountReason) {
            // قفل صف المجموعة لتسلسل قرارات السعة المتزامنة.
            $locked = Cohort::whereKey($cohort->getKey())->lockForUpdate()->firstOrFail();

            // منع التسجيل المكرر لنفس الطالب في نفس المجموعة.
            $exists = Enrollment::where('cohort_id', $locked->id)
                ->where('person_id', $person->id)
                ->whereNull('deleted_at')
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'person_id' => ['الطالب مسجَّل في هذه المجموعة بالفعل.'],
                ]);
            }

            $occupied = Enrollment::where('cohort_id', $locked->id)
                ->whereIn('status', Enrollment::SEAT_OCCUPYING)
                ->count();

            $hasSeat = $locked->capacity === 0 || $occupied < $locked->capacity;
            $status = $hasSeat ? 'pending_invoice' : 'waitlisted';

            $snapshot = $this->computeSnapshot((string) $locked->price, (string) $locked->tax_rate, (string) $discount);

            return Enrollment::create([
                'organization_id' => $locked->organization_id,
                'cohort_id' => $locked->id,
                'person_id' => $person->id,
                'status' => $status,
                'price_snapshot' => $snapshot['price'],
                'tax_rate_snapshot' => $snapshot['tax_rate'],
                'discount_amount_snapshot' => $snapshot['discount'],
                'tax_amount_snapshot' => $snapshot['tax'],
                'total_snapshot' => $snapshot['total'],
                'discount_reason' => $discountReason,
                'enrolled_at' => now(),
            ]);
        });
    }

    /**
     * حساب القيم بدقة Decimal (bcmath، scale=2). PRD §17, ADR-006.
     */
    private function computeSnapshot(string $price, string $taxRate, string $discount): array
    {
        // الخصم لا يتجاوز السعر
        if (bccomp($discount, $price, 2) === 1) {
            $discount = $price;
        }
        $taxable = bcsub($price, $discount, 2);
        $tax = bcdiv(bcmul($taxable, $taxRate, 6), '100', 2); // taxable * rate / 100
        $total = bcadd($taxable, $tax, 2);

        return [
            'price' => $price,
            'tax_rate' => $taxRate,
            'discount' => bcadd($discount, '0', 2),
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
