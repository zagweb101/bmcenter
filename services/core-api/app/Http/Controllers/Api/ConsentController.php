<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsentRequest;
use App\Http\Resources\ConsentResource;
use App\Models\Consent;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API الموافقات (Consent). PRD §19.4.
 * كل موافقة مسنودة بدليل (قناة، نص، إصدار، IP/UA، تاريخ منح/سحب).
 */
class ConsentController extends Controller
{
    public function index(Person $person): AnonymousResourceCollection
    {
        // ربط الشخص يطبّق OrganizationScope → 404 عبر المؤسسات.
        $consents = $person->consents()->latest()->paginate(30);

        return ConsentResource::collection($consents);
    }

    public function store(StoreConsentRequest $request, Person $person): ConsentResource
    {
        $data = $request->validated();
        $status = $data['status'] ?? 'granted';

        $consent = $person->consents()->create([
            'organization_id' => $person->organization_id,
            'purpose' => $data['purpose'],
            'text_version' => $data['text_version'],
            'text_snapshot' => $data['text_snapshot'] ?? null,
            'channel' => $data['channel'] ?? null,
            'status' => $status,
            'granted_at' => $status === 'granted' ? now() : null,
            'withdrawn_at' => $status === 'withdrawn' ? now() : null,
            'evidence_ip' => $request->ip(),
            'evidence_user_agent' => $request->userAgent(),
            'evidence_reference' => $request->header('X-Consent-Reference'),
        ]);

        return new ConsentResource($consent);
    }

    public function withdraw(Consent $consent): ConsentResource
    {
        $consent->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        return new ConsentResource($consent);
    }
}
