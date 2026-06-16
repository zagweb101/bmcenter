<?php

/**
 * إعداد بوابة الدفع. PRD §18, ADR-004/ADR-005.
 * للربط: غيّر PAYMENT_DRIVER إلى المزوّد المعتمد وزوّد المفاتيح + سر الـ Webhook.
 */
return [
    // simulation = يعمل الآن | <provider> = المزوّد السعودي المعتمد عند الربط
    'driver' => env('PAYMENT_DRIVER', 'simulation'),

    // سر توقيع الـ Webhook (HMAC) — المصدر الموثوق لحالة الدفع (ADR-005)
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', 'sim-webhook-secret'),

    // مفاتيح المزوّد (تُملأ عند الربط — لا تُخزَّن في الكود §6)
    'api_key' => env('PAYMENT_API_KEY'),
    'base_url' => env('PAYMENT_BASE_URL'),
];
