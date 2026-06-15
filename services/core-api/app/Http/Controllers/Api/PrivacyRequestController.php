<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrivacyRequestRequest;
use App\Http\Requests\UpdatePrivacyRequestRequest;
use App\Http\Resources\PrivacyRequestResource;
use App\Models\PrivacyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API طلبات حقوق صاحب البيانات (PDPL). PRD §19.5.
 * Workflow موثّق مع التحقق من الهوية والسبب والنتيجة.
 */
class PrivacyRequestController extends Controller
{
    // المدة النظامية الافتراضية للرد (قابلة للتهيئة لاحقًا عبر Settings).
    private const RESPONSE_WINDOW_DAYS = 30;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PrivacyRequest::query()->latest();

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return PrivacyRequestResource::collection($query->paginate(30));
    }

    public function store(StorePrivacyRequestRequest $request): PrivacyRequestResource
    {
        $data = $request->validated();

        $privacyRequest = PrivacyRequest::create([
            'person_id' => $data['person_id'] ?? null,
            'type' => $data['type'],
            'subject_reason' => $data['subject_reason'] ?? null,
            'status' => 'received',
            'due_at' => now()->addDays(self::RESPONSE_WINDOW_DAYS),
        ]);

        return new PrivacyRequestResource($privacyRequest);
    }

    public function show(PrivacyRequest $privacyRequest): PrivacyRequestResource
    {
        return new PrivacyRequestResource($privacyRequest);
    }

    public function update(UpdatePrivacyRequestRequest $request, PrivacyRequest $privacyRequest): PrivacyRequestResource
    {
        $data = $request->validated();
        $resolved = in_array($data['status'], ['fulfilled', 'rejected'], true);

        $privacyRequest->update([
            'status' => $data['status'],
            'identity_verified' => $data['identity_verified'] ?? $privacyRequest->identity_verified,
            'verification_method' => $data['verification_method'] ?? $privacyRequest->verification_method,
            'resolution_note' => $data['resolution_note'] ?? $privacyRequest->resolution_note,
            'handled_by_user_id' => $request->user()->id,
            'resolved_at' => $resolved ? now() : $privacyRequest->resolved_at,
        ]);

        return new PrivacyRequestResource($privacyRequest);
    }
}
