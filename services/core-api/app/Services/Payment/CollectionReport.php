<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * تجميع تحصيل يوم (Daily Collection). PRD §8.3.
 * مُصفّى بالمؤسسة عبر OrganizationScope على Payment.
 */
class CollectionReport
{
    public function forDate(string $date): array
    {
        $rows = Payment::query()
            ->where('status', 'confirmed')
            ->whereRaw('paid_at::date = ?::date', [$date])
            ->selectRaw('method, sum(amount) as sum_amount, count(*) as cnt')
            ->groupBy('method')
            ->get();

        $byMethod = [];
        $total = '0';
        $count = 0;
        foreach ($rows as $row) {
            $byMethod[$row->method] = bcadd((string) $row->sum_amount, '0', 2);
            $total = bcadd($total, (string) $row->sum_amount, 2);
            $count += (int) $row->cnt;
        }

        return [
            'date' => $date,
            'totals_by_method' => $byMethod,
            'total_amount' => $total,
            'payments_count' => $count,
        ];
    }
}
