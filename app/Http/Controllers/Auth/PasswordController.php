<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordController extends Controller
{
    private function statusMessage(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Un email de réinitialisation a été envoyé. Pensez à vérifier vos spams.',
            Password::PASSWORD_RESET => 'Votre mot de passe a été mis à jour.',
            Password::INVALID_TOKEN => 'Lien de réinitialisation invalide ou expiré.',
            default => 'Une erreur est survenue. Veuillez réessayer.',
        };
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = (string) $request->input('email');
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (!$user || $user->is_active === false) {
            return back()->withErrors([
                'email' => 'Aucun compte actif ne correspond à cette adresse email.',
            ])->onlyInput('email');
        }

        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (TransportExceptionInterface $e) {
            // Important: ce catch est volontairement "user-friendly", mais on log l'exception pour diagnostiquer.
            // (Sinon on se retrouve avec un message générique alors que l'email de test peut sembler fonctionner.)
            report($e);

            $details = '';
            if (app()->environment('local') || (bool) config('app.debug')) {
                $details = ' Détails SMTP: ' . $e->getMessage();
            }
            return back()->withErrors([
                'email' => "Impossible d'envoyer l'email de réinitialisation pour le moment. Vérifiez la configuration SMTP puis réessayez." . $details,
            ])->onlyInput('email');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with([
                'status' => $this->statusMessage($status),
            ]);
        }

        return back()->withErrors([
            'email' => 'Impossible d’envoyer l’email de réinitialisation pour le moment. Réessayez plus tard.',
        ])->onlyInput('email');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Auth::login($user);
                $request->session()->regenerate();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('home')->with('success', 'Mot de passe mis à jour. Bienvenue sur Talashow !');
        }

        return back()->withErrors([
            'email' => $this->statusMessage((string) $status),
        ]);
    }
}

