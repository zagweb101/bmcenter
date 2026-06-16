<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\PersonResource;
use App\Models\AuditLog;
use App\Models\Person;
use App\Services\Audit\AuditLogger;
use App\Services\Person\PersonMatcher;
use App\Support\Contact\ContactNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API الأشخاص (Person 360). PRD §11.
 * كل القراءات/الكتابات مُصفّاة بالمؤسسة عبر OrganizationScope (ADR-003).
 */
class PersonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Person::query()->latest();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where('full_name', 'ilike', "%{$search}%");
        }

        return PersonResource::collection($query->paginate(20));
    }

    public function store(StorePersonRequest $request, PersonMatcher $matcher): JsonResponse
    {
        $data = $request->validated();

        // منع التكرار — Exact Match (PRD §11): يُرجع 409 مع الشخص الموجود.
        if ($existing = $matcher->findExact($data)) {
            return response()->json([
                'message' => 'يوجد شخص مطابق بالفعل (منع التكرار).',
                'match' => new PersonResource($existing),
            ], 409);
        }

        $phoneE164 = ContactNormalizer::phoneToE164($data['phone'] ?? null);

        $person = Person::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'phone_national' => $data['phone'] ?? null,
            'phone_e164' => $phoneE164,
            'country_code' => $phoneE164 ? substr($phoneE164, 0, 4) : null,
            'email' => ContactNormalizer::email($data['email'] ?? null),
            'national_id_hash' => ContactNormalizer::nationalIdHash($data['national_id'] ?? null),
            'national_id_encrypted' => $data['national_id'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'nationality' => $data['nationality'] ?? null,
        ]);

        return (new PersonResource($person))->response()->setStatusCode(201);
    }

    public function show(Person $person): PersonResource
    {
        // ربط النموذج يطبّق OrganizationScope تلقائيًا → 404 عبر المؤسسات.
        return new PersonResource($person);
    }

    /**
     * مرشّحو المطابقة الاحتمالية (للمراجعة قبل الإنشاء). PRD §11.
     */
    public function matchCandidates(Request $request, PersonMatcher $matcher): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $candidates = $matcher->findProbable($data)->map(fn (Person $p) => [
            'person' => new PersonResource($p),
            'match_score' => round((float) $p->match_score, 3),
        ]);

        return response()->json(['candidates' => $candidates]);
    }

    /**
     * Activity Timeline — سجل تدقيق الشخص. PRD §8.1 (gated audit.view).
     */
    public function activity(Person $person): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->where('subject_type', $person->getMorphClass())
            ->where('subject_id', $person->getKey())
            ->latest()
            ->paginate(30);

        return AuditLogResource::collection($logs);
    }

    /**
     * كشف الهوية الوطنية — وصول لبيانات حساسة يُسجَّل دائمًا. PRD §11 (gated persons.viewSensitive).
     */
    public function revealNationalId(Person $person, AuditLogger $audit): JsonResponse
    {
        $audit->logSensitiveAccess($person, ['national_id']);

        return response()->json([
            'person_id' => $person->id,
            'national_id' => $person->national_id_encrypted, // يُفك تشفيره عبر الـ cast
        ]);
    }
}
