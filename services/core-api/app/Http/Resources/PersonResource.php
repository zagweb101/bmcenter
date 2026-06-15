<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * تمثيل الشخص في الـ API — لا يكشف الحقول الحساسة. PRD §11, §22.
 */
class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone_e164' => $this->phone_e164,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'is_merged' => $this->merged_into_person_id !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            // ملاحظة: national_id_* لا يُعاد إطلاقًا (بيانات حساسة).
        ];
    }
}
