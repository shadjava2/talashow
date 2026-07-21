<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        if (!auth()->check() || !auth()->user()->hasPermission($permissionKey)) {
            \App\Services\SecurityAuditService::securityEvent('permission_denied', 'low', [
                'permission' => $permissionKey,
            ], $request);

            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}

