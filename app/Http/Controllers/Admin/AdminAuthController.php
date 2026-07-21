<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->canAccessAdminApp()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = (string) $credentials['email'];
        $user = User::query()->where('email', $email)->first();

        if ($user && $user->admin_locked_until && $user->admin_locked_until->isFuture()) {
            SecurityAuditService::securityEvent('admin_login_blocked_locked', 'medium', [
                'email' => $email,
                'user_id' => $user->id,
            ], $request);

            return back()->withErrors([
                'email' => 'Trop de tentatives. Réessayez dans quelques minutes.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($user) {
                $failures = (int) $user->admin_login_failures + 1;
                $user->admin_login_failures = min(32767, $failures);
                if ($user->admin_login_failures >= 10) {
                    $user->admin_locked_until = now()->addMinutes(30);
                    SecurityAuditService::securityEvent('admin_account_locked', 'high', [
                        'user_id' => $user->id,
                    ], $request);
                }
                $user->save();
            }

            SecurityAuditService::securityEvent('admin_login_failed', 'low', [
                'email' => $email,
            ], $request);

            return back()->withErrors([
                'email' => 'Identifiants invalides.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! Auth::user()->canAccessAdminApp()) {
            Auth::logout();
            SecurityAuditService::securityEvent('admin_login_denied_no_app_access', 'medium', [
                'email' => $email,
            ], $request);

            return back()->withErrors([
                'email' => 'Accès admin refusé.',
            ])->onlyInput('email');
        }

        $authUser = Auth::user();
        $authUser->admin_login_failures = 0;
        $authUser->admin_locked_until = null;
        $authUser->save();

        SecurityAuditService::adminActivity('admin.login_success', [], $request);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        SecurityAuditService::adminActivity('admin.logout', [], $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
