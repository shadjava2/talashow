<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev/LAN helper:
 * When accessing the app via a LAN IP (ex: http://100.x.x.x:8000), ensure generated URLs
 * (route(), redirect()->route(), etc.) use the current host instead of APP_URL=localhost.
 *
 * This avoids redirects/forms pointing to localhost on remote devices.
 */
class SetAppUrlFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $root = $request->getSchemeAndHttpHost();
            if ($root) {
                config(['app.url' => $root]);
                URL::forceRootUrl($root);
                URL::forceScheme($request->getScheme());
            }
        }

        return $next($request);
    }
}

