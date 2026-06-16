<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** تحقق تسجيل دفعة يدوية. PRD §17, §18. */
class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:payments.manage
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:cash,bank_transfer,sadad,gateway'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'person_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'integer'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'gt:0'],
        ];
    }
}
