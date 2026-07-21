<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * Requêtes admin (/talashow-admin/*) → page de connexion admin, sinon login frontend.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }
        return $request->is('talashow-admin', 'talashow-admin/*')
            ? route('admin.login')
            : route('login');
    }
}
