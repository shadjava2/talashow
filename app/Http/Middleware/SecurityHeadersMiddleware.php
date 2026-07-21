<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(self), gyroscope=(self), microphone=(), camera=(), geolocation=(), payment=(self)'
        );

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.(int) config('security.hsts_max_age', 31536000).'; includeSubDomains; preload'
            );
        }

        $mode = strtolower((string) config('security.csp_mode', 'enforce'));
        if ($mode !== 'off') {
            $csp = $this->contentSecurityPolicy();
            $header = $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $response->headers->set($header, $csp);
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self' https://checkout.stripe.com https://hooks.stripe.com https://www.paypal.com https://www.sandbox.paypal.com",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "img-src 'self' data: blob: https: http:",
            "font-src 'self' data: https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://www.paypal.com https://www.paypalobjects.com https://www.sandbox.paypal.com https://embed.tawk.to https://*.tawk.to",
            "connect-src 'self' https: wss:",
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://checkout.stripe.com https://www.paypal.com https://www.sandbox.paypal.com https://*.paypal.com https://*.bunnycdn.com https://*.b-cdn.net https://*.mediadelivery.net https://iframe.mediadelivery.net https://player.mediadelivery.net https://video.bunnycdn.com https://embed.tawk.to https://*.tawk.to https://accounts.google.com https://www.facebook.com https://appleid.apple.com",
            "media-src 'self' blob: https://*.bunnycdn.com https://*.b-cdn.net https://*.mediadelivery.net",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
        ];

        return implode('; ', $directives);
    }
}
