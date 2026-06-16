<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
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
}
