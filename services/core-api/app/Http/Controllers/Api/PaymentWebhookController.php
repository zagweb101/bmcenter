<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GatewayEvent;
use App\Models\PaymentIntent;
use App\Services\Payment\Gateway\Contracts\PaymentGateway;
use App\Services\Payment\PaymentService;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * مستقبِل Webhook الدفع. PRD §18, ADR-005.
 * المصدر الموثوق لحالة الدفع: موقّع + Idempotent + منع Replay.
 * عام (بلا مصادقة مستخدم) — الأمان عبر توقيع HMAC.
 */
class PaymentWebhookController extends Controller
{
    public function handle(string $provider, Request $request, PaymentGateway $gateway, PaymentService $payments): JsonResponse
    {
        $raw = $request->getContent();
        $signature = $request->header('X-Signature');

        // 1) التحقق من التوقيع (منع الانتحال).
        if (! $gateway->verifyWebhook($raw, $signature)) {
            return response()->json(['message' => 'توقيع غير صالح.'], 400);
        }

        $event = $gateway->parseWebhook($raw);

        // 2) Idempotency: لا تُعالَج نفس الحدث مرتين.
        $record = GatewayEvent::firstOrCreate(
            ['provider' => $provider, 'event_id' => $event['event_id']],
            ['type' => $event['type'] ?? null, 'payload' => $event],
        );
        if (! $record->wasRecentlyCreated) {
            return response()->json(['message' => 'تمت المعالجة مسبقًا.'], 200);
        }

        // 3) معالجة الدفع الناجح.
        if (($event['status'] ?? null) === 'succeeded' && ! empty($event['reference'])) {
            $intent = PaymentIntent::withoutGlobalScopes()->where('reference', $event['reference'])->first();

            if ($intent) {
                // سياق المؤسسة من النية الموثوقة (لا من العميل).
                app(Tenancy::class)->set((int) $intent->organization_id);

                $amount = $event['amount'] ?? (string) $intent->amount;
                $payments->recordManual([
                    'organization_id' => $intent->organization_id,
                    'person_id' => $intent->person_id,
                    'method' => 'gateway',
                    'amount' => $amount,
                    'reference' => $intent->reference,
                    'gateway_txn_ref' => $event['reference'],
                    'allocations' => $intent->invoice_id
                        ? [['invoice_id' => $intent->invoice_id, 'amount' => $amount]]
                        : [],
                ]);

                $intent->update(['status' => 'succeeded']);
                app(Tenancy::class)->forget();
            }
        }

        $record->update(['processed_at' => now()]);

        return response()->json(['message' => 'تمت المعالجة.'], 200);
    }
}
