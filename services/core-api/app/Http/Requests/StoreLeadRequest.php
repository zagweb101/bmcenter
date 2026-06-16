<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * تحقق إنشاء Lead. PRD §13 (كل Lead له Source).
 */
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:leads.manage
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'lead_source_id' => ['required', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'owner_user_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
