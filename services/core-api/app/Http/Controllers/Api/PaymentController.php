<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Services\Payment\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** API المدفوعات (المسار اليدوي). PRD §17, §18. */
class PaymentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PaymentResource::collection(
            Payment::query()->with('receipt')->latest()->paginate(20)
        );
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load('allocations', 'receipt'));
    }

    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $payment = $service->recordManual($request->validated());

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    /**
     * استرداد (Refund) من دفعة. PRD §17, §27.
     */
    public function refund(Request $request, Payment $payment, RefundService $service): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $refund = $service->refund($payment, (string) $data['amount'], $data['reason'] ?? null, $request->user()->id);

        return response()->json([
            'id' => $refund->id,
            'payment_id' => $refund->payment_id,
            'amount' => $refund->amount,
            'status' => $refund->status,
            'remaining_refundable' => $payment->fresh()->refundable(),
        ], 201);
    }
}
