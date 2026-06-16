<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cohort_id' => $this->cohort_id,
            'person_id' => $this->person_id,
            'status' => $this->status,
            'price_snapshot' => $this->price_snapshot,
            'tax_rate_snapshot' => $this->tax_rate_snapshot,
            'discount_amount_snapshot' => $this->discount_amount_snapshot,
            'tax_amount_snapshot' => $this->tax_amount_snapshot,
            'total_snapshot' => $this->total_snapshot,
            'discount_reason' => $this->discount_reason,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
        ];
    }
}
