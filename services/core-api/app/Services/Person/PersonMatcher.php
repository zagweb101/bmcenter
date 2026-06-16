<?php

namespace App\Services\Person;

use App\Models\Person;
use App\Support\Contact\ContactNormalizer;
use Illuminate\Support\Collection;

/**
 * منع التكرار — Exact Match فقط في MVP-0A. PRD §11.
 * يبحث داخل المؤسسة الحالية (عبر OrganizationScope) عن تطابق تام
 * في الجوال (E.164) أو البريد أو hash الهوية، فيمنع الإنشاء المكرر.
 * (الترقية إلى Probabilistic Match مؤجلة إلى 0B.)
 */
class PersonMatcher
{
    /**
     * يعيد الشخص المطابق تمامًا إن وُجد، وإلا null.
     */
    public function findExact(array $attributes): ?Person
    {
        $phone = ContactNormalizer::phoneToE164($attributes['phone'] ?? null);
        $email = ContactNormalizer::email($attributes['email'] ?? null);
        $nidHash = ContactNormalizer::nationalIdHash($attributes['national_id'] ?? null);

        if ($phone === null && $email === null && $nidHash === null) {
            return null;
        }

        return Person::query()
            ->where(function ($q) use ($phone, $email, $nidHash) {
                if ($phone !== null) {
                    $q->orWhere('phone_e164', $phone);
                }
                if ($email !== null) {
                    $q->orWhere('email', $email);
                }
                if ($nidHash !== null) {
                    $q->orWhere('national_id_hash', $nidHash);
                }
            })
            ->first();
    }

    /**
     * مطابقة احتمالية (Probabilistic Match) — للمراجعة لا للمنع. PRD §11 (0B).
     * المعايير: تشابه الاسم (pg_trgm) + تطابق آخر 9 أرقام (أرقام بديلة/اختلاف مفتاح الدولة) + البريد.
     * يُصفّى بالمؤسسة تلقائيًا عبر OrganizationScope.
     *
     * @return Collection<int, Person> أشخاص بحقل إضافي match_score
     */
    public function findProbable(array $attributes, int $limit = 10, float $nameThreshold = 0.3): Collection
    {
        $name = trim((string) ($attributes['full_name'] ?? $attributes['first_name'] ?? ''));
        $phoneE164 = ContactNormalizer::phoneToE164($attributes['phone'] ?? null);
        $last9 = $phoneE164 ? substr($phoneE164, -9) : null;
        $email = ContactNormalizer::email($attributes['email'] ?? null);

        if ($name === '' && $last9 === null && $email === null) {
            return collect();
        }

        return Person::query()
            ->selectRaw(
                "persons.*, GREATEST(
                    CASE WHEN ?::text <> '' THEN similarity(coalesce(full_name,''), ?::text) ELSE 0 END,
                    CASE WHEN ?::text IS NOT NULL AND right(coalesce(phone_e164,''), 9) = ?::text THEN 1 ELSE 0 END,
                    CASE WHEN ?::text IS NOT NULL AND email = ?::text THEN 1 ELSE 0 END
                ) AS match_score",
                [$name, $name, $last9, $last9, $email, $email],
            )
            ->where(function ($q) use ($name, $nameThreshold, $last9, $email) {
                if ($name !== '') {
                    $q->orWhereRaw("similarity(coalesce(full_name,''), ?::text) > ?::float8", [$name, $nameThreshold]);
                }
                if ($last9 !== null) {
                    $q->orWhereRaw("right(coalesce(phone_e164,''), 9) = ?::text", [$last9]);
                }
                if ($email !== null) {
                    $q->orWhere('email', $email);
                }
            })
            ->orderByDesc('match_score')
            ->limit($limit)
            ->get();
    }
}
