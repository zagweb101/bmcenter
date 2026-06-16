<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCohortRequest;
use App\Http\Resources\CohortResource;
use App\Models\Cohort;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** API المجموعات (Cohort). PRD §14. */
class CohortController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CohortResource::collection(
            Cohort::query()->withCount('enrollments')->latest()->paginate(20)
        );
    }

    public function store(StoreCohortRequest $request): JsonResponse
    {
        $data = $request->validated();

        // الدورة يجب أن تخصّ المؤسسة الحالية (OrganizationScope)
        if (! Course::whereKey($data['course_id'])->exists()) {
            throw ValidationException::withMessages(['course_id' => ['دورة غير صالحة.']]);
        }

        $cohort = Cohort::create([
            ...$data,
            'status' => 'enrollment_open',
        ]);

        return (new CohortResource($cohort))->response()->setStatusCode(201);
    }
}
