<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'reference' => $this->reference,
            'person_id' => $this->person_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? [
                'receipt_number' => $this->receipt->receipt_number,
                'amount' => $this->receipt->amount,
                'issued_at' => $this->receipt->issued_at?->toIso8601String(),
            ] : null),
            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($a) => [
                'invoice_id' => $a->invoice_id,
                'amount' => $a->amount,
            ])),
        ];
    }
}
