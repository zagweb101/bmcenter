<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * تمثيل طلب حق صاحب البيانات. PRD §19.5.
 */
class PrivacyRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'type' => $this->type,
            'status' => $this->status,
            'subject_reason' => $this->subject_reason,
            'identity_verified' => $this->identity_verified,
            'verification_method' => $this->verification_method,
            'handled_by_user_id' => $this->handled_by_user_id,
            'due_at' => $this->due_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolution_note' => $this->resolution_note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
