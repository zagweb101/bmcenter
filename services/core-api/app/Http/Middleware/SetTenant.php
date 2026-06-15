<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يضبط سياق المؤسسة من المستخدم المصادَق فقط. PRD §12, ADR-003.
 * لا تُقبل قيمة organization_id من العميل كمصدر ثقة.
 */
class SetTenant
{
    public function __construct(protected Tenancy $tenancy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // يُحلّ المستخدم عبر حارس sanctum (التوكن) — يعمل قبل auth:sanctum وقبل ربط النماذج.
        $user = $request->user() ?? auth('sanctum')->user();

        // يُضبط دائمًا (set أو forget) لمنع تسرّب سياق مؤسسة من طلب سابق (Octane/الاختبارات).
        if ($user && $user->organization_id) {
            $this->tenancy->set((int) $user->organization_id);
        } else {
            $this->tenancy->forget();
        }

        return $next($request);
    }
}
