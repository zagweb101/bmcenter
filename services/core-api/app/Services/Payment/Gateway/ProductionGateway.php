<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Contracts\PaymentGateway;
use RuntimeException;

/**
 * مشغّل الإنتاج لبوابة الدفع — نقطة الربط الفعلي. PRD §18.
 *
 * عند الربط، يُنفَّذ هنا استدعاء المزوّد السعودي المعتمد:
 *  - Hosted Checkout/Tokenization، 3D Secure، mada/Apple Pay.
 *  - verifyWebhook بتوقيع المزوّد الحقيقي + منع Replay.
 *  - Refund كامل/جزئي + Settlement/Reconcile.
 *
 * يبقى فارغًا عمدًا حتى اختيار المزوّد واعتماد الـ Sandbox.
 */
class ProductionGateway implements PaymentGateway
{
    private function notWired(): never
    {
        throw new RuntimeException(
            'ربط بوابة الدفع الإنتاجي غير مُفعَّل بعد: يلزم اختيار المزوّد والمفاتيح واعتماد Sandbox (PRD §18). '
            . 'استخدم PAYMENT_DRIVER=simulation حتى اكتمال الربط.'
        );
    }

    public function createIntent(array $data): array
    {
        $this->notWired();
    }

    public function retrieveTransaction(string $reference): array
    {
        $this->notWired();
    }

    public function capture(string $reference): array
    {
        $this->notWired();
    }

    public function refund(string $reference, string $amount): array
    {
        $this->notWired();
    }

    public function verifyWebhook(string $rawPayload, ?string $signature): bool
    {
        $this->notWired();
    }

    public function parseWebhook(string $rawPayload): array
    {
        $this->notWired();
    }
}
