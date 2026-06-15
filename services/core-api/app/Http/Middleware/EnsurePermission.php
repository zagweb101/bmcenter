<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فرض الصلاحيات على الخادم (RBAC). PRD §6, §22.
 * الاستخدام في المسارات: ->middleware('permission:persons.manage')
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'ليست لديك صلاحية تنفيذ هذا الإجراء.');
        }

        return $next($request);
    }
}
