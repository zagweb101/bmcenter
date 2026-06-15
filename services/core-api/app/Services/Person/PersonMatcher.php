<?php

namespace App\Services\Person;

use App\Models\Person;
use App\Support\Contact\ContactNormalizer;

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
}
