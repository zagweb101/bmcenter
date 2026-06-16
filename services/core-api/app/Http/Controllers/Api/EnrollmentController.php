<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Person;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** API التسجيل (Enrollment). PRD §14, §15, §27. */
class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Enrollment::query()->latest();

        if ($request->filled('cohort_id')) {
            $query->where('cohort_id', $request->integer('cohort_id'));
        }
        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return EnrollmentResource::collection($query->paginate(20));
    }

    public function store(StoreEnrollmentRequest $request, EnrollmentService $service): JsonResponse
    {
        $data = $request->validated();

        // الكيانات يجب أن تخصّ المؤسسة الحالية (OrganizationScope → null إن كانت لمؤسسة أخرى)
        $cohort = Cohort::find($data['cohort_id']);
        $person = Person::find($data['person_id']);
        if (! $cohort || ! $person) {
            throw ValidationException::withMessages([
                'cohort_id' => ['المجموعة أو الطالب غير صالح ضمن المؤسسة.'],
            ]);
        }

        $enrollment = $service->enroll(
            $cohort,
            $person,
            (string) ($data['discount_amount'] ?? '0'),
            $data['discount_reason'] ?? null,
            $request->user(),
        );

        return (new EnrollmentResource($enrollment))->response()->setStatusCode(201);
    }

    public function show(Enrollment $enrollment): EnrollmentResource
    {
        return new EnrollmentResource($enrollment);
    }

    /**
     * إلغاء التسجيل — يحرّر المقعد. PRD §14.
     */
    public function cancel(Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->update(['status' => 'cancelled']);

        return new EnrollmentResource($enrollment);
    }

    /**
     * نقل التسجيل إلى مجموعة أخرى. PRD §14.
     */
    public function transfer(Request $request, Enrollment $enrollment, EnrollmentService $service): JsonResponse
    {
        $data = $request->validate(['cohort_id' => ['required', 'integer']]);

        $target = Cohort::find($data['cohort_id']);
        if (! $target) {
            throw ValidationException::withMessages([
                'cohort_id' => ['المجموعة الهدف غير صالحة ضمن المؤسسة.'],
            ]);
        }

        $new = $service->transfer($enrollment, $target);

        return (new EnrollmentResource($new))->response()->setStatusCode(201);
    }
}
