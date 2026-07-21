<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        if ($user && $user->is_active === false) {
            // Autoriser la déconnexion même si le compte est bloqué
            if ($request->is('logout') || $request->is('talashow-admin/logout')) {
                return $next($request);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $route = $request->is('talashow-admin/*') ? 'admin.login' : 'login';
            return redirect()->route($route)->with('error', 'Votre compte est bloqué. Veuillez contacter le support.');
        }

        return $next($request);
    }
}

