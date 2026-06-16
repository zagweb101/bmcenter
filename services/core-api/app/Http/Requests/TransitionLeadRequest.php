<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * تحقق تغيير مرحلة Lead. PRD §13.
 * قواعد: الإغلاق كمفقود (lost) يتطلب سببًا؛ مراحل النظام لا تُضبط يدويًا.
 */
class TransitionLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:leads.manage
    }

    public function rules(): array
    {
        $manualStages = array_values(array_diff(Lead::STAGES, Lead::SYSTEM_STAGES));

        return [
            'stage' => ['required', Rule::in($manualStages)],
            'lost_reason' => ['nullable', 'string', 'max:255', 'required_if:stage,lost'],
        ];
    }

    public function messages(): array
    {
        return [
            'lost_reason.required_if' => 'سبب الإغلاق إلزامي عند وضع العميل كمفقود.',
            'stage.in' => 'مرحلة غير صالحة أو لا تُضبط يدويًا.',
        ];
    }
}
