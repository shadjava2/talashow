<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->normalize($request->session()->get('locale'))
            ?? $this->normalize($request->cookie('locale'))
            ?? $this->fromHeader((string) $request->header('Accept-Language', ''))
            ?? config('app.locale', 'fr');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale', 'fr');
        }

        app()->setLocale($locale);

        // Resynchronise session si seule la cookie (ou l’inverse) est valide
        if ($request->hasSession() && $request->session()->get('locale') !== $locale) {
            $request->session()->put('locale', $locale);
        }

        $response = $next($request);

        // HTML localisé : ne pas laisser proxies / SW servir une vieille langue
        if ($request->isMethod('GET') && ! $request->ajax() && str_contains((string) $request->header('Accept'), 'text/html')) {
            $response->headers->set('Vary', 'Cookie', false);
            $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        }

        return $response;
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $locale = strtolower(trim((string) $value));

        return in_array($locale, self::SUPPORTED, true) ? $locale : null;
    }

    private function fromHeader(string $acceptLanguage): ?string
    {
        $acceptLanguage = strtolower($acceptLanguage);
        if ($acceptLanguage === '') {
            return null;
        }

        // Parse minimal : premier tag fr* / en*
        if (preg_match('/\b(fr|en)\b/i', $acceptLanguage, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }
}

