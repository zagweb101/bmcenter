<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** تمثيل Lead في الـ API. PRD §13. */
class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone_e164' => $this->phone_e164,
            'email' => $this->email,
            'stage' => $this->stage,
            'status' => $this->status,
            'lost_reason' => $this->lost_reason,
            'owner_user_id' => $this->owner_user_id,
            'person_id' => $this->person_id,
            'lead_source_id' => $this->lead_source_id,
            'campaign_id' => $this->campaign_id,
            'next_follow_up_at' => $this->next_follow_up_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
