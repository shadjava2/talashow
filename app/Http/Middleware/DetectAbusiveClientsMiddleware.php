<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAbusiveClientsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ua = (string) $request->userAgent();
        $isEmptyUa = trim($ua) === '' || $ua === '-';

        if ($isEmptyUa && ($request->is('talashow-admin') || $request->is('talashow-admin/*'))) {
            SecurityAuditService::securityEvent('empty_user_agent', 'medium', [
                'path' => $request->path(),
            ], $request);

            return response('Forbidden', 403);
        }

        if ($isEmptyUa && ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE'))) {
            SecurityAuditService::securityEvent('empty_user_agent_post', 'low', [
                'path' => $request->path(),
            ], $request);
        }

        return $next($request);
    }
}
