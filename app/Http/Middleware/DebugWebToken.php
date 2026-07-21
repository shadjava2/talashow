<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugWebToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) env('TALASHOW_DEBUG_TOKEN', '');
        $provided = (string) $request->query('debug', '');

        // Si un token correct est fourni, on active le debug web temporairement (10 minutes).
        if (app()->environment('local') && $token !== '' && $provided !== '' && hash_equals($token, $provided)) {
            $request->session()->put('talashow.debug_web_enabled_until', now()->addMinutes(10)->timestamp);
        }

        return $next($request);
    }
}

