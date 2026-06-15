<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * تحقق إنشاء طلب حق صاحب البيانات. PRD §19.5.
 */
class StorePrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية تُفرض عبر middleware('permission:privacy.handle')
    }

    public function rules(): array
    {
        return [
            'person_id' => ['nullable', 'integer', 'exists:persons,id'],
            'type' => ['required', 'in:access,copy,rectify,erase,withdraw_consent,inform'],
            'subject_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
