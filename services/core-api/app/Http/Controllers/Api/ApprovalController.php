<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\Enrollment;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * API طلبات الاعتماد (Approvals). PRD §15.
 * اعتماد/رفض الخصومات المتجاوزة لحد الدور؛ لا تنفيذ قبل الاعتماد.
 */
class ApprovalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ApprovalRequest::query()->latest();
        $status = $request->string('status')->trim()->value() ?: 'pending';
        $query->where('status', $status);

        return ApprovalRequestResource::collection($query->paginate(20));
    }

    public function approve(Request $request, ApprovalRequest $approval, EnrollmentService $service): ApprovalRequestResource
    {
        $this->ensurePending($approval);
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']]);

        // تطبيق الأثر بحسب النوع
        if ($approval->type === 'discount' && $approval->approvable_type === (new Enrollment)->getMorphClass()) {
            $enrollment = Enrollment::findOrFail($approval->approvable_id);
            $service->applyApprovedDiscount($enrollment, (string) $approval->amount, $approval->reason);
        }

        $approval->update([
            'status' => 'approved',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ]);

        return new ApprovalRequestResource($approval);
    }

    public function reject(Request $request, ApprovalRequest $approval): ApprovalRequestResource
    {
        $this->ensurePending($approval);
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']]);

        // الرفض: التسجيل يكمل دون الخصم (يخرج من pending_approval).
        if ($approval->type === 'discount' && $approval->approvable_type === (new Enrollment)->getMorphClass()) {
            $enrollment = Enrollment::find($approval->approvable_id);
            if ($enrollment && $enrollment->status === 'pending_approval') {
                $enrollment->update(['status' => 'pending_invoice']);
            }
        }

        $approval->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ]);

        return new ApprovalRequestResource($approval);
    }

    private function ensurePending(ApprovalRequest $approval): void
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['تمت معالجة هذا الطلب بالفعل.'],
            ]);
        }
    }
}
