<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** تحقق إنشاء مجموعة. PRD §14. */
class StoreCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:courses.manage
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
