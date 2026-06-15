<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * تحقق معالجة طلب حق صاحب البيانات (Workflow). PRD §19.5.
 */
class UpdatePrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية تُفرض عبر middleware('permission:privacy.handle')
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:received,verifying,in_progress,fulfilled,rejected'],
            'identity_verified' => ['nullable', 'boolean'],
            'verification_method' => ['nullable', 'string', 'max:128'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
