<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Services\SecurityAuditService;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    private function regOtpCacheKey(string $email): string
    {
        return 'talashow.regotp.' . sha1(Str::lower(trim($email)));
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if (!str_contains($email, '@')) {
            return $email;
        }
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = strlen($local) <= 2 ? ($local[0] ?? '*') . '*' : substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));
        return $localMasked . '@' . $domain;
    }

    private function ttlFromExpiresAt(int $expiresAtTs): \DateTimeInterface
    {
        $seconds = max(1, $expiresAtTs - now()->timestamp);
        return now()->addSeconds($seconds);
    }

    private function otpCacheData(string $email): array
    {
        $cacheKey = $this->regOtpCacheKey($email);
        $data = Cache::get($cacheKey);
        return [$cacheKey, $data];
    }

    private function otpRestart(Request $request, string $cacheKey, string $message)
    {
        Cache::forget($cacheKey);
        $request->session()->forget('regotp_email');
        return redirect()->route('register')->with('error', $message);
    }

    private function otpGetValidDataOrResponse(Request $request, string $email): array
    {
        [$cacheKey, $data] = $this->otpCacheData($email);
        $expired = !$data || empty($data['expires_at']) || now()->timestamp > (int) $data['expires_at'];
        if ($expired) {
            return [$cacheKey, null, $this->otpRestart($request, $cacheKey, 'Votre code OTP a expiré. Veuillez recommencer.')];
        }

        return [$cacheKey, $data, null];
    }

    private function otpCheckAttemptsOrResponse(Request $request, string $cacheKey, array $data): array
    {
        $attempts = (int) ($data['attempts'] ?? 0);
        if ($attempts >= 5) {
            return [$attempts, $this->otpRestart($request, $cacheKey, 'Trop de tentatives. Veuillez recommencer.')];
        }
        return [$attempts, null];
    }

    private function otpCheckCodeOrResponse(string $cacheKey, array $data, int $attempts, string $otp)
    {
        if (Hash::check($otp, (string) ($data['otp_hash'] ?? ''))) {
            return null;
        }

        $data['attempts'] = $attempts + 1;
        Cache::put($cacheKey, $data, $this->ttlFromExpiresAt((int) ($data['expires_at'] ?? now()->addMinutes(10)->timestamp)));
        return back()->withErrors([
            'otp' => 'Code OTP incorrect.',
        ])->withInput();
    }

    private function otpFinalize(Request $request, string $email, array $data, string $cacheKey)
    {
        $roleUser = Role::firstOrCreate(['key' => 'user'], ['name' => 'User']);
        // Important: l'email est unique en DB même avec soft deletes → si l'utilisateur est "trashed",
        // on le restaure au lieu de recréer (sinon erreur SQL duplicate).
        $user = User::withTrashed()->where('email', $email)->first();
        if ($user && method_exists($user, 'trashed') && $user->trashed()) {
            $user->restore();
        }

        if (!$user) {
            $user = new User();
            $user->email = $email;
            $user->coins = 0;
            $user->reward_coins = 0;
        }

        // Remettre les champs essentiels à jour (au cas où l'ancien compte était supprimé)
        $user->role_id = $roleUser->id;
        $user->name = (string) ($data['name'] ?? 'User');
        $user->password = (string) ($data['password_hash'] ?? '');
        $user->email_verified_at = now();
        $user->is_active = true;

        try {
            $user->save();
        } catch (\Throwable $e) {
            // Fallback : si un compte existe déjà (conflit), on récupère et continue au lieu d'une 500.
            report($e);
            $user = User::withTrashed()->where('email', $email)->firstOrFail();
            if (method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }
        }

        Cache::forget($cacheKey);
        $request->session()->forget('regotp_email');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Bienvenue sur Talashow ! Votre compte est prêt.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            if (Auth::user() && Auth::user()->is_active === false) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Votre compte est bloqué. Veuillez contacter le support.',
                ])->onlyInput('email');
            }
            return redirect()->intended('/');
        }

        SecurityAuditService::securityEvent('auth_login_failed', 'low', [
            'email' => (string) $credentials['email'],
        ], $request);

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function registerStart(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Autorise la réinscription si un compte a été soft-delete
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Anti-spam / anti-bruteforce : max 5 envois par 10 min (par email + IP)
        $email = $validated['email'];
        $ip = $request->ip() ?? 'unknown';
        $throttleKey = 'regotp:send:' . sha1(Str::lower($email) . '|' . $ip);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors([
                'email' => 'Trop de tentatives. Réessayez dans quelques minutes.',
            ])->onlyInput('email');
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->regOtpCacheKey($email);
        $payload = [
            'name' => $validated['name'],
            'email' => $email,
            // On ne stocke jamais le mot de passe en clair.
            'password_hash' => Hash::make($validated['password']),
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];
        Cache::put($cacheKey, $payload, $this->ttlFromExpiresAt((int) $payload['expires_at']));

        try {
            Mail::to($email)->send(new TemplateMail('auth.otp', [
                'name' => $validated['name'],
                'otp' => $otp,
                'expires_minutes' => 10,
            ]));
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
            report($e);
            return redirect()
                ->route('register')
                ->withErrors([
                    'email' => "Impossible d'envoyer le code OTP pour le moment. Vérifiez la configuration email (SMTP) puis réessayez.",
                ])
                ->withInput($request->only('name', 'email'));
        }
        RateLimiter::hit($throttleKey, 600);

        $request->session()->put('regotp_email', $email);

        return redirect()->route('otp.form')->with('success', 'Code OTP envoyé. Vérifiez votre email pour finaliser la création du compte.');
    }

    public function showOtp(Request $request)
    {
        $email = (string) $request->session()->get('regotp_email', '');
        if (!$email) {
            return redirect()->route('register')->with('error', 'Veuillez d’abord saisir votre email pour recevoir un code OTP.');
        }

        $cacheKey = $this->regOtpCacheKey($email);
        $data = Cache::get($cacheKey);
        if (!$data || empty($data['expires_at']) || now()->timestamp > (int) $data['expires_at']) {
            Cache::forget($cacheKey);
            return redirect()->route('register')->with('error', 'Votre code OTP a expiré. Veuillez recommencer.');
        }

        return view('auth.verify-otp', [
            'emailMasked' => $this->maskEmail($email),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $email = (string) $request->session()->get('regotp_email', '');
        if (!$email) {
            return redirect()->route('register')->with('error', 'Session invalide. Veuillez recommencer.');
        }

        [$cacheKey, $data, $resp] = $this->otpGetValidDataOrResponse($request, $email);
        $attempts = 0;

        if (!$resp) {
            [$attempts, $resp2] = $this->otpCheckAttemptsOrResponse($request, $cacheKey, (array) $data);
            $resp = $resp2;
        }

        if (!$resp) {
            $otp = (string) $request->input('otp');
            $resp = $this->otpCheckCodeOrResponse($cacheKey, (array) $data, (int) $attempts, $otp);
        }

        if ($resp) {
            return $resp;
        }

        return $this->otpFinalize($request, $email, (array) $data, $cacheKey);
    }

    public function resendOtp(Request $request)
    {
        $email = (string) $request->session()->get('regotp_email', '');
        $response = null;

        if (!$email) {
            $response = redirect()->route('register')->with('error', 'Veuillez recommencer l’inscription.');
        } else {
            $cacheKey = $this->regOtpCacheKey($email);
            $data = Cache::get($cacheKey);
            if (!$data) {
                $response = redirect()->route('register')->with('error', 'Aucun code en cours. Veuillez recommencer.');
            } else {
                $sentAt = (int) ($data['sent_at'] ?? 0);
                if ($sentAt && (now()->timestamp - $sentAt) < 60) {
                    $response = back()->with('error', 'Veuillez patienter 1 minute avant de renvoyer un nouveau code.');
                } else {
                    $otp = (string) random_int(100000, 999999);
                    $data['otp_hash'] = Hash::make($otp);
                    $data['attempts'] = 0;
                    $data['sent_at'] = now()->timestamp;
                    $data['expires_at'] = now()->addMinutes(10)->timestamp;

                    Cache::put($cacheKey, $data, $this->ttlFromExpiresAt((int) $data['expires_at']));

                    try {
                        Mail::to($email)->send(new TemplateMail('auth.otp', [
                            'name' => (string) ($data['name'] ?? 'Utilisateur'),
                            'otp' => $otp,
                            'expires_minutes' => 10,
                        ]));
                        $response = back()->with('success', 'Nouveau code OTP envoyé.');
                    } catch (\Throwable $e) {
                        report($e);
                        $response = redirect()
                            ->route('otp.form')
                            ->with('error', "Impossible d'envoyer le code OTP pour le moment. Vérifiez la configuration email (SMTP) puis réessayez.");
                    }
                }
            }
        }

        return $response ?? redirect()->route('register')->with('error', 'Une erreur est survenue. Veuillez recommencer.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // OAuth Social Login
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                    ]);
                } else {
                    $roleUser = Role::firstOrCreate(['key' => 'user'], ['name' => 'User']);
                    $user = User::create([
                        'role_id' => $roleUser->id,
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'avatar' => $socialUser->getAvatar(),
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'email_verified_at' => now(),
                    ]);
                }
            }

            Auth::login($user);

            return redirect('/');
        } catch (\Exception $e) {
            return redirect('/login')->withErrors([
                'error' => 'Erreur lors de la connexion avec ' . $provider,
            ]);
        }
    }
}
