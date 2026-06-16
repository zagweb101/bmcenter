<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** تحقق إنشاء دورة. PRD §14. */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:courses.manage
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:64'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
