<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Pages d’erreur “pro” (dark) pour le web.
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            // Non connecté : laisser Laravel rediriger vers la page de login (admin ou frontend)
            if ($e instanceof AuthenticationException) {
                return null;
            }

            // Mode debug "web" (Ignition/stacktrace) sécurisé:
            // - uniquement en env local
            // - activé via middleware DebugWebToken: visite d'une URL avec ?debug=TOKEN
            //   => stocke un flag en session (10 min) pour couvrir aussi les POST (ex: /register)
            $until = (int) ($request->session()->get('talashow.debug_web_enabled_until', 0) ?? 0);
            if (app()->environment('local') && $until > 0 && now()->timestamp <= $until) {
                return null; // laisse Laravel afficher la stacktrace (Ignition)
            }

            // Permet de forcer une UX "pro" même en local (évite Ignition/stack pour l’utilisateur)
            $friendly = (bool) env('TALASHOW_FRIENDLY_ERRORS', true);
            if (app()->hasDebugModeEnabled() && !$friendly) {
                return null;
            }

            // Erreurs SMTP (OTP / reset password) -> message clair + retour arrière
            if ($e instanceof TransportExceptionInterface) {
                report($e);
                try {
                    // Back() dépend du referer. En accès IP / environnements réseau, le referer peut être absent.
                    // On préfère une redirection sûre.
                    $ref = (string) ($request->headers->get('referer') ?? '');
                    if ($ref !== '') {
                        return redirect()->to($ref)->with('error', "Impossible d'envoyer l'email pour le moment. Vérifiez la configuration SMTP puis réessayez.");
                    }
                    return redirect()->route('register')->with('error', "Impossible d'envoyer l'email pour le moment. Vérifiez la configuration SMTP puis réessayez.");
                } catch (\Throwable) {
                    // Si on ne peut pas redirect (pas de referer/session), on affiche une page 500 pro.
                    return response()->view('errors.500', [], 500);
                }
            }

            // 404: routes inexistantes ou modèles introuvables
            if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
                return response()->view('errors.404', [], 404);
            }

            // HTTP errors (403, 419, etc.)
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $view = "errors.{$status}";
                if (view()->exists($view)) {
                    // Important: certaines erreurs (ex: abort(500)) arrivent ici et ne sont pas loggées sinon.
                    if ($status >= 500) {
                        report($e);
                    }
                    return response()->view($view, [], $status);
                }
            }

            // Fallback: 500
            report($e);
            return response()->view('errors.500', [], 500);
        });
    }
}
