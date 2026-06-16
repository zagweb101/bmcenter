<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'document_number' => $this->document_number,
            'enrollment_id' => $this->enrollment_id,
            'buyer_person_id' => $this->buyer_person_id,
            'invoice_type_code' => $this->invoice_type_code,
            'transaction_type' => $this->transaction_type,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'total_including_tax' => $this->total_including_tax,
            'tax_breakdown' => $this->tax_breakdown,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
