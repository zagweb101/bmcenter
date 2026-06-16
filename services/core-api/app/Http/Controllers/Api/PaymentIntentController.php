<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Services\Payment\Gateway\Contracts\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** نوايا الدفع عبر البوابة. PRD §18. */
class PaymentIntentController extends Controller
{
    public function store(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'person_id' => ['nullable', 'integer'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        $result = $gateway->createIntent(['amount' => (string) $data['amount']]);

        $intent = PaymentIntent::create([
            'person_id' => $data['person_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'provider' => config('payments.driver'),
            'reference' => $result['reference'],
            'amount' => (string) $data['amount'],
            'currency' => 'SAR',
            'status' => $result['status'] ?? 'requires_payment',
        ]);

        return response()->json([
            'reference' => $intent->reference,
            'status' => $intent->status,
            'client_secret' => $result['client_secret'] ?? null,
        ], 201);
    }
}
