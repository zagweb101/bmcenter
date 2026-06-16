<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** تحقق إنشاء تسجيل. PRD §14, §15. */
class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:enrollments.manage
    }

    public function rules(): array
    {
        return [
            'cohort_id' => ['required', 'integer'],
            'person_id' => ['required', 'integer'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
