<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * تحقق تسجيل موافقة على الخادم. PRD §19.4.
 * الأغراض منفصلة (privacy/marketing/whatsapp/media_publishing/community).
 */
class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية تُفرض عبر middleware('permission:consents.manage')
    }

    public function rules(): array
    {
        return [
            'purpose' => ['required', 'in:privacy,marketing,whatsapp,media_publishing,community'],
            'text_version' => ['required', 'string', 'max:64'],
            'text_snapshot' => ['nullable', 'string'],
            'channel' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'in:granted,withdrawn'],
        ];
    }
}
