<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
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
}
