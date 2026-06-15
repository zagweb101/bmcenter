<?php

namespace App\Support\Contact;

/**
 * تطبيع وسائل التواصل لأغراض المطابقة (Exact Match). PRD §11.
 * السوق الأول السعودية — الافتراضي مفتاح +966.
 */
class ContactNormalizer
{
    /**
     * يحوّل رقم الجوال إلى صيغة E.164 قدر الإمكان.
     * أمثلة سعودية: 0501234567 / 966501234567 / +966 50 123 4567 → +966501234567
     */
    public static function phoneToE164(?string $raw, string $defaultCountry = '966'): ?string
    {
        if ($raw === null) {
            return null;
        }

        $hasPlus = str_starts_with(trim($raw), '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        // 00 بادئة دولية
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        // يبدأ بمفتاح الدولة الافتراضي
        if (str_starts_with($digits, $defaultCountry)) {
            return '+' . $digits;
        }

        // صيغة وطنية تبدأ بصفر (0501234567)
        if (str_starts_with($digits, '0')) {
            return '+' . $defaultCountry . substr($digits, 1);
        }

        // افتراض رقم وطني بلا صفر
        return '+' . $defaultCountry . $digits;
    }

    public static function email(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = strtolower(trim($raw));

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * hash للهوية الوطنية للمطابقة دون كشف (PRD §11 — تشفير/تقنيع).
     */
    public static function nationalIdHash(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits === '' ? null : hash('sha256', $digits);
    }
}
