<?php

namespace App\Services\Enrollment;

use App\Models\ApprovalRequest;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Person;
use App\Models\User;
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
    public function enroll(
        Cohort $cohort,
        Person $person,
        float|string $discount = 0,
        ?string $discountReason = null,
        ?User $actingUser = null,
    ): Enrollment {
        return DB::transaction(function () use ($cohort, $person, $discount, $discountReason, $actingUser) {
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

            // بوابة اعتماد الخصم (PRD §15): ما يتجاوز حد الدور لا يُطبَّق قبل الاعتماد.
            $requested = bcadd((string) $discount, '0', 2);
            $limit = $actingUser?->maxDiscountLimit(); // null = غير محدود
            $needsApproval = bccomp($requested, '0', 2) === 1
                && $limit !== null
                && bccomp($requested, $limit, 2) === 1;

            // الخصم المطبَّق فعليًا: صفر إن كان يحتاج اعتمادًا (يُطبَّق لاحقًا عند الموافقة).
            $appliedDiscount = $needsApproval ? '0' : $requested;

            if (! $hasSeat) {
                $status = 'waitlisted';
            } elseif ($needsApproval) {
                $status = 'pending_approval';
            } else {
                $status = 'pending_invoice';
            }

            $snapshot = $this->computeSnapshot((string) $locked->price, (string) $locked->tax_rate, $appliedDiscount);

            $enrollment = Enrollment::create([
                'organization_id' => $locked->organization_id,
                'cohort_id' => $locked->id,
                'person_id' => $person->id,
                'status' => $status,
                'price_snapshot' => $snapshot['price'],
                'tax_rate_snapshot' => $snapshot['tax_rate'],
                'discount_amount_snapshot' => $snapshot['discount'],
                'tax_amount_snapshot' => $snapshot['tax'],
                'total_snapshot' => $snapshot['total'],
                'discount_reason' => $needsApproval ? null : $discountReason,
                'enrolled_at' => now(),
            ]);

            if ($needsApproval) {
                ApprovalRequest::create([
                    'organization_id' => $locked->organization_id,
                    'approvable_type' => $enrollment->getMorphClass(),
                    'approvable_id' => $enrollment->id,
                    'type' => 'discount',
                    'amount' => $requested,
                    'reason' => $discountReason,
                    'status' => 'pending',
                    'requested_by_user_id' => $actingUser?->id,
                ]);
            }

            return $enrollment;
        });
    }

    /**
     * نقل تسجيل إلى مجموعة أخرى. PRD §14.
     * يحرّر مقعد المجموعة القديمة (status=transferred) ويُنشئ تسجيلًا في الهدف
     * مع فحص سعة الهدف وإعادة حساب الـ Snapshot على سعر/ضريبة الهدف (مع حمل الخصم).
     */
    public function transfer(Enrollment $enrollment, Cohort $target): Enrollment
    {
        if (in_array($enrollment->status, ['transferred', 'cancelled', 'completed'], true)) {
            throw ValidationException::withMessages([
                'enrollment' => ['لا يمكن نقل تسجيل بحالته الحالية.'],
            ]);
        }
        if ($enrollment->cohort_id === $target->id) {
            throw ValidationException::withMessages([
                'cohort_id' => ['الطالب مسجَّل في هذه المجموعة بالفعل.'],
            ]);
        }

        return DB::transaction(function () use ($enrollment, $target) {
            $lockedTarget = Cohort::whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            $duplicate = Enrollment::where('cohort_id', $lockedTarget->id)
                ->where('person_id', $enrollment->person_id)
                ->whereNull('deleted_at')
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'person_id' => ['الطالب مسجَّل في المجموعة الهدف بالفعل.'],
                ]);
            }

            $occupied = Enrollment::where('cohort_id', $lockedTarget->id)
                ->whereIn('status', Enrollment::SEAT_OCCUPYING)
                ->count();
            $hasSeat = $lockedTarget->capacity === 0 || $occupied < $lockedTarget->capacity;

            // إعادة الحساب على سعر/ضريبة الهدف مع حمل الخصم المعتمَد سابقًا.
            $snapshot = $this->computeSnapshot(
                (string) $lockedTarget->price,
                (string) $lockedTarget->tax_rate,
                (string) $enrollment->discount_amount_snapshot,
            );

            // تحرير المقعد القديم.
            $enrollment->update(['status' => 'transferred']);

            return Enrollment::create([
                'organization_id' => $lockedTarget->organization_id,
                'cohort_id' => $lockedTarget->id,
                'person_id' => $enrollment->person_id,
                'status' => $hasSeat ? 'pending_invoice' : 'waitlisted',
                'price_snapshot' => $snapshot['price'],
                'tax_rate_snapshot' => $snapshot['tax_rate'],
                'discount_amount_snapshot' => $snapshot['discount'],
                'tax_amount_snapshot' => $snapshot['tax'],
                'total_snapshot' => $snapshot['total'],
                'discount_reason' => $enrollment->discount_reason,
                'enrolled_at' => now(),
            ]);
        });
    }

    /**
     * تطبيق خصم معتمَد على تسجيل قائم — يعيد حساب الـ Snapshot من السعر/الضريبة المخزّنين.
     * PRD §15: بعد الاعتماد يُطبَّق الخصم.
     */
    public function applyApprovedDiscount(Enrollment $enrollment, string $amount, ?string $reason): void
    {
        $snapshot = $this->computeSnapshot(
            (string) $enrollment->price_snapshot,
            (string) $enrollment->tax_rate_snapshot,
            $amount,
        );

        $enrollment->update([
            'discount_amount_snapshot' => $snapshot['discount'],
            'tax_amount_snapshot' => $snapshot['tax'],
            'total_snapshot' => $snapshot['total'],
            'discount_reason' => $reason,
            // الخروج من pending_approval إلى المسار الطبيعي (إن لم يكن في قائمة انتظار).
            'status' => $enrollment->status === 'pending_approval' ? 'pending_invoice' : $enrollment->status,
        ]);
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
