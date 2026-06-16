<?php

namespace App\Services\Payment\Gateway\Contracts;

/**
 * طبقة تجريد بوابة الدفع. PRD §18, ADR-004.
 * تُبدَّل بين Simulation/Production عبر الإعداد. الـWebhook هو مصدر الحالة (ADR-005).
 */
interface PaymentGateway
{
    /** ينشئ نية دفع ويعيد مرجعها وبيانات إكمالها. */
    public function createIntent(array $data): array;

    /** يسترجع حالة معاملة لدى المزوّد. */
    public function retrieveTransaction(string $reference): array;

    /** يؤكّد الالتقاط (capture) لمعاملة. */
    public function capture(string $reference): array;

    /** استرداد كامل/جزئي. */
    public function refund(string $reference, string $amount): array;

    /** يتحقق من توقيع الـ Webhook (HMAC) ويمنع الانتحال. */
    public function verifyWebhook(string $rawPayload, ?string $signature): bool;

    /** يحوّل حمولة الـ Webhook إلى حدث موحّد. */
    public function parseWebhook(string $rawPayload): array;
}
