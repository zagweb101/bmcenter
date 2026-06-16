<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\TransitionLeadRequest;
use App\Http\Resources\LeadResource;
use App\Http\Resources\PersonResource;
use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Models\LeadSource;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonMatcher;
use App\Support\Contact\ContactNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * API العملاء المحتملين (Leads / CRM). PRD §13.
 * كل القراءات/الكتابات مُصفّاة بالمؤسسة (OrganizationScope).
 */
class LeadController extends Controller
{
    public function sources(): JsonResponse
    {
        return response()->json([
            'data' => LeadSource::where('is_active', true)->get(['id', 'key', 'name_ar']),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Lead::query()->latest();

        if ($stage = $request->string('stage')->trim()->value()) {
            $query->where('stage', $stage);
        }
        if ($request->filled('owner_user_id')) {
            $query->where('owner_user_id', $request->integer('owner_user_id'));
        }
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return LeadResource::collection($query->paginate(20));
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $data = $request->validated();

        // المصدر يجب أن يخصّ المؤسسة الحالية (OrganizationScope يحجب غيرها)
        if (! LeadSource::whereKey($data['lead_source_id'])->exists()) {
            throw ValidationException::withMessages([
                'lead_source_id' => ['مصدر غير صالح.'],
            ]);
        }

        $this->ensureUserInOrg($data['owner_user_id'] ?? null);

        $lead = Lead::create([
            'full_name' => $data['full_name'],
            'phone_e164' => ContactNormalizer::phoneToE164($data['phone'] ?? null),
            'email' => ContactNormalizer::email($data['email'] ?? null),
            'lead_source_id' => $data['lead_source_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'stage' => isset($data['owner_user_id']) ? 'assigned' : 'new',
            'status' => 'open',
        ]);

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Lead $lead): LeadResource
    {
        return new LeadResource($lead);
    }

    /**
     * إسناد المالك (Owner). PRD §13 — تغيير الموظف لا يفقد السجل (مُسجَّل في Audit).
     */
    public function assign(Request $request, Lead $lead): LeadResource
    {
        $validated = $request->validate([
            'owner_user_id' => ['required', 'integer'],
        ]);
        $this->ensureUserInOrg($validated['owner_user_id']);

        $lead->update([
            'owner_user_id' => $validated['owner_user_id'],
            'stage' => $lead->stage === 'new' ? 'assigned' : $lead->stage,
        ]);

        return new LeadResource($lead);
    }

    /**
     * تغيير المرحلة وفق قواعد Pipeline. PRD §13.
     */
    public function transition(TransitionLeadRequest $request, Lead $lead): LeadResource
    {
        $data = $request->validated();
        $isLost = $data['stage'] === 'lost';

        $lead->update([
            'stage' => $data['stage'],
            'status' => $isLost ? 'lost' : 'open',
            'lost_reason' => $isLost ? $data['lost_reason'] : null,
        ]);

        return new LeadResource($lead);
    }

    /**
     * تسجيل تفاعل مع Lead. PRD §13.
     */
    public function addInteraction(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', LeadInteraction::TYPES)],
            'body' => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $lead->interactions()->create([
            'organization_id' => $lead->organization_id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'body' => $data['body'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return response()->json(['message' => 'تم تسجيل التفاعل.'], 201);
    }

    /**
     * تحويل Lead إلى Person (Person Match). PRD §11, §8.2.
     * يربط بشخص مطابق تمامًا إن وُجد (منع التكرار)، وإلا يُنشئ شخصًا جديدًا.
     */
    public function convert(Lead $lead, PersonMatcher $matcher): JsonResponse
    {
        if ($lead->person_id) {
            return response()->json([
                'message' => 'العميل مرتبط بشخص بالفعل.',
                'person' => new PersonResource($lead->person),
                'matched' => true,
            ]);
        }

        $existing = $matcher->findExact([
            'phone' => $lead->phone_e164,
            'email' => $lead->email,
        ]);

        if ($existing) {
            $lead->update(['person_id' => $existing->id]);

            return response()->json([
                'message' => 'تم الربط بشخص مطابق (منع التكرار).',
                'person' => new PersonResource($existing),
                'matched' => true,
            ]);
        }

        $person = Person::create([
            'first_name' => $lead->full_name ?: 'غير مسمّى',
            'full_name' => $lead->full_name,
            'phone_e164' => $lead->phone_e164,
            'country_code' => $lead->phone_e164 ? substr($lead->phone_e164, 0, 4) : null,
            'email' => $lead->email,
        ]);

        $lead->update(['person_id' => $person->id]);

        return response()->json([
            'message' => 'تم إنشاء شخص جديد وربطه.',
            'person' => new PersonResource($person),
            'matched' => false,
        ], 201);
    }

    /**
     * يتحقق أن المستخدم (المالك المُسنَد) يخصّ المؤسسة الحالية. PRD §12.
     */
    private function ensureUserInOrg(?int $userId): void
    {
        if ($userId === null) {
            return;
        }
        $org = app(\App\Support\Tenancy\Tenancy::class)->id();
        $ok = User::where('id', $userId)->where('organization_id', $org)->exists();
        if (! $ok) {
            throw ValidationException::withMessages([
                'owner_user_id' => ['المستخدم لا يخصّ هذه المؤسسة.'],
            ]);
        }
    }
}
