<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Contracts\PaymentGateway;
use Illuminate\Support\Str;

/**
 * مشغّل محاكاة بوابة الدفع — يعمل بالكامل بلا مزوّد. PRD §18.
 * يحاكي إنشاء النية والتحقق من توقيع الـWebhook (HMAC) والتحليل.
 */
class SimulationGateway implements PaymentGateway
{
    public function createIntent(array $data): array
    {
        $ref = 'sim_' . Str::uuid()->toString();

        return [
            'reference' => $ref,
            'status' => 'requires_payment',
            'client_secret' => 'cs_' . Str::random(24),
            'amount' => (string) ($data['amount'] ?? '0'),
        ];
    }

    public function retrieveTransaction(string $reference): array
    {
        return ['reference' => $reference, 'status' => 'succeeded'];
    }

    public function capture(string $reference): array
    {
        return ['reference' => $reference, 'status' => 'succeeded'];
    }

    public function refund(string $reference, string $amount): array
    {
        return ['reference' => $reference, 'amount' => $amount, 'status' => 'refunded'];
    }

    public function verifyWebhook(string $rawPayload, ?string $signature): bool
    {
        if ($signature === null) {
            return false;
        }
        $expected = hash_hmac('sha256', $rawPayload, (string) config('payments.webhook_secret'));

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(string $rawPayload): array
    {
        $data = json_decode($rawPayload, true) ?: [];

        return [
            'event_id' => $data['id'] ?? Str::uuid()->toString(),
            'type' => $data['type'] ?? 'payment.succeeded',
            'reference' => $data['reference'] ?? null,
            'amount' => isset($data['amount']) ? (string) $data['amount'] : null,
            'status' => $data['status'] ?? 'succeeded',
        ];
    }
}
