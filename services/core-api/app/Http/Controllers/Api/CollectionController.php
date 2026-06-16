<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Services\Payment\CollectionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** تقارير التحصيل والإقفال اليومي. PRD §8.3. */
class CollectionController extends Controller
{
    public function dailyReport(Request $request, CollectionReport $report): JsonResponse
    {
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        return response()->json($report->forDate($date));
    }

    public function index(): JsonResponse
    {
        return response()->json(
            CashClosing::query()->latest('closing_date')->paginate(30)
        );
    }

    /**
     * إقفال يوم — يحفظ لقطة المحصّلات ويمنع إعادة الإقفال. PRD §8.3.
     */
    public function close(Request $request, CollectionReport $report): JsonResponse
    {
        $data = $request->validate(['date' => ['nullable', 'date']]);
        $date = isset($data['date'])
            ? \Illuminate\Support\Carbon::parse($data['date'])->toDateString()
            : now()->toDateString();

        if (CashClosing::where('closing_date', $date)->exists()) {
            throw ValidationException::withMessages(['date' => ['تم إقفال هذا اليوم بالفعل.']]);
        }

        $summary = $report->forDate($date);

        $closing = CashClosing::create([
            'closing_date' => $date,
            'totals_by_method' => $summary['totals_by_method'],
            'total_amount' => $summary['total_amount'],
            'payments_count' => $summary['payments_count'],
            'closed_by_user_id' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return response()->json($closing, 201);
    }
}
