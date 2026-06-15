<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * تمثيل الموافقة في الـ API. PRD §19.4.
 */
class ConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'purpose' => $this->purpose,
            'text_version' => $this->text_version,
            'channel' => $this->channel,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'granted_at' => $this->granted_at?->toIso8601String(),
            'withdrawn_at' => $this->withdrawn_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
