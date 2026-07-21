<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'source' => 'nullable|string|max:40',
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $ip = $request->ip() ?? 'unknown';
        $throttleKey = 'newsletter:subscribe:' . sha1($email . '|' . $ip);
        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            return $this->respond($request, false, "Trop de tentatives. Réessayez dans quelques minutes.", 429);
        }

        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();
        if (!$subscriber) {
            $subscriber = new NewsletterSubscriber();
            $subscriber->email = $email;
            $subscriber->unsubscribe_token = $this->makeToken();
        }

        // Réinscription possible
        $subscriber->unsubscribed_at = null;
        $subscriber->locale = app()->getLocale();
        $subscriber->source = !empty($validated['source']) ? (string) $validated['source'] : ($subscriber->source ?: null);
        $subscriber->ip = $request->ip();
        $subscriber->user_agent = substr((string) $request->userAgent(), 0, 1000);

        // Déjà confirmé -> OK (idempotent)
        if ($subscriber->confirmed_at !== null) {
            $subscriber->save();
            RateLimiter::hit($throttleKey, 600);
            return $this->respond($request, true, "Vous êtes déjà inscrit(e). Merci !", 200);
        }

        // Double opt-in: on régénère un token de confirmation
        $subscriber->confirm_token = $this->makeToken();
        $subscriber->save();

        $confirmUrl = route('newsletter.confirm', $subscriber->confirm_token);
        $unsubscribeUrl = route('newsletter.unsubscribe', $subscriber->unsubscribe_token);

        try {
            Mail::to($email)->send(new TemplateMail('marketing.newsletter_confirm', [
                'confirm_url' => $confirmUrl,
                'unsubscribe_url' => $unsubscribeUrl,
            ]));
        } catch (\Throwable $e) {
            report($e);
            return $this->respond($request, false, "Impossible d'envoyer l'email de confirmation pour le moment. Réessayez plus tard.", 500);
        }

        RateLimiter::hit($throttleKey, 600);
        return $this->respond($request, true, "Merci ! Vérifiez votre email pour confirmer votre inscription.", 200);
    }

    public function resend(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $ip = $request->ip() ?? 'unknown';
        $throttleKey = 'newsletter:resend:' . sha1($email . '|' . $ip);
        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            return $this->respond($request, false, "Trop de tentatives. Réessayez dans quelques minutes.", 429);
        }

        $subscriber = NewsletterSubscriber::query()
            ->where('email', $email)
            ->whereNull('unsubscribed_at')
            ->first();

        if (!$subscriber) {
            RateLimiter::hit($throttleKey, 600);
            return $this->respond($request, false, "Aucune inscription en attente pour cet email. Utilisez “Me prévenir” pour vous inscrire.", 404);
        }

        if ($subscriber->confirmed_at !== null) {
            RateLimiter::hit($throttleKey, 600);
            return $this->respond($request, true, "Cet email est déjà confirmé. Merci !", 200);
        }

        // Régénère un token de confirmation (lien neuf)
        $subscriber->confirm_token = $this->makeToken();
        $subscriber->locale = app()->getLocale();
        $subscriber->ip = $request->ip();
        $subscriber->user_agent = substr((string) $request->userAgent(), 0, 1000);
        $subscriber->save();

        $confirmUrl = route('newsletter.confirm', $subscriber->confirm_token);
        $unsubscribeUrl = route('newsletter.unsubscribe', $subscriber->unsubscribe_token);

        try {
            Mail::to($email)->send(new TemplateMail('marketing.newsletter_confirm', [
                'confirm_url' => $confirmUrl,
                'unsubscribe_url' => $unsubscribeUrl,
            ]));
        } catch (\Throwable $e) {
            report($e);
            return $this->respond($request, false, "Impossible d'envoyer l'email de confirmation pour le moment. Réessayez plus tard.", 500);
        }

        RateLimiter::hit($throttleKey, 600);
        return $this->respond($request, true, "Lien de confirmation renvoyé. Vérifiez votre email.", 200);
    }

    public function confirm(string $token)
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('confirm_token', $token)
            ->whereNull('unsubscribed_at')
            ->first();

        if (!$subscriber) {
            // Cas fréquent: lien déjà utilisé (confirm_token remis à null après confirmation) ou lien ancien.
            // On renvoie un message clair plutôt qu’un "invalide" brut.
            return redirect()->route('home')->with('error',
                "Lien de confirmation invalide, expiré ou déjà utilisé. " .
                "Si vous n’êtes pas encore inscrit(e), refaites “Me prévenir” pour recevoir un nouveau lien."
            );
        }

        $subscriber->confirmed_at = now();
        $subscriber->confirm_token = null;
        $subscriber->save();

        return redirect()->route('home')->with('success', "Inscription newsletter confirmée. Merci !");
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->first();

        if (!$subscriber) {
            return redirect()->route('home')->with('error', "Lien de désinscription invalide.");
        }

        $subscriber->unsubscribed_at = now();
        $subscriber->confirm_token = null;
        $subscriber->save();

        // Best-effort: email de confirmation de désinscription
        try {
            Mail::to($subscriber->email)->send(new TemplateMail('marketing.newsletter_unsubscribed', []));
        } catch (\Throwable) {
            // ignore
        }

        return redirect()->route('home')->with('success', "Vous êtes désinscrit(e).");
    }

    private function makeToken(): string
    {
        return Str::random(60);
    }

    private function respond(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $status);
        }

        return back()->with($success ? 'success' : 'error', $message)->withInput();
    }
}

