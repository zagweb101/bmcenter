<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * تحقق إنشاء شخص على الخادم. PRD §6, §19.3 (تقليل البيانات — الهوية اختيارية).
 */
class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية تُفرض عبر middleware('permission:persons.manage')
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:64'],
        ];
    }
}
