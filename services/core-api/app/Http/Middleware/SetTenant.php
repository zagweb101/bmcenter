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
        $user = $request->user();

        if ($user && $user->organization_id) {
            $this->tenancy->set((int) $user->organization_id);
        }

        return $next($request);
    }
}
