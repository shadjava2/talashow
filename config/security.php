<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy (mode report-only ou enforce)
    |--------------------------------------------------------------------------
    | CSP permissive mais explicite pour Bunny, Stripe, PayPal, OAuth, Tawk.
    | Ajuster via .env : SECURITY_CSP_MODE=enforce|report|off
    */
    'csp_mode' => env('SECURITY_CSP_MODE', 'enforce'),

    /*
    |--------------------------------------------------------------------------
    | HSTS (Strict-Transport-Security)
    |--------------------------------------------------------------------------
    | Activé uniquement si la requête est HTTPS et APP_ENV=production.
    */
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

];
