<?php

/**
 * إعداد ZATCA (فاتورة). PRD §16, ADR-004 (عزل خلف واجهة قابلة للاستبدال).
 * للربط الفعلي: غيّر ZATCA_DRIVER=production وزوّد بيانات الاعتماد/CSID في Secrets Vault.
 */
return [
    // simulation = يعمل الآن بالكامل بلا هيئة | production = يتطلب Onboarding/CSID
    'driver' => env('ZATCA_DRIVER', 'simulation'),

    'environment' => env('ZATCA_ENV', 'sandbox'), // sandbox | simulation | production

    // نقاط النهاية الرسمية (تُملأ عند الربط)
    'base_url' => env('ZATCA_BASE_URL'),

    // مرجع الوحدة/الشهادة في Secrets Vault — لا تُخزَّن المفاتيح كنص صريح (§16.4)
    'egs_unit_id' => env('ZATCA_EGS_UNIT_ID'),
];
