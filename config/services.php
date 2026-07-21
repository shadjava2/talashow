<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paiement (Stripe)
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth (Socialite)
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/google/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/facebook/callback',
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/apple/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bunny Storage (images : posters, covers, miniatures, logo)
    |--------------------------------------------------------------------------
    | Zone Storage + mot de passe API ; URL publique = pull zone (ex. https://xxx.b-cdn.net).
    */
    'bunny_storage' => [
        'zone_name' => env('BUNNY_STORAGE_ZONE_NAME'),
        'region' => env('BUNNY_STORAGE_REGION', 'de'),
        'api_key' => env('BUNNY_STORAGE_API_KEY'),
        'cdn_url' => rtrim((string) env('BUNNY_STORAGE_CDN_URL', ''), '/'),
        'verify_ssl' => filter_var(env('BUNNY_STORAGE_VERIFY_SSL', env('BUNNY_VERIFY_SSL', true)), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bunny Stream (video.bunnycdn.com)
    |--------------------------------------------------------------------------
    */
    'bunny_stream' => [
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
        'api_key' => env('BUNNY_STREAM_API_KEY'),
        'cdn_hostname' => env('BUNNY_STREAM_CDN_HOSTNAME'),
        'pull_zone' => env('BUNNY_STREAM_PULL_ZONE'),
        'api_base' => rtrim((string) env('BUNNY_STREAM_API_BASE', 'https://video.bunnycdn.com'), '/'),
        'embed_base' => rtrim((string) env('BUNNY_STREAM_EMBED_BASE', 'https://iframe.mediadelivery.net/embed'), '/'),
        'player_iframe_base' => rtrim((string) env('BUNNY_STREAM_PLAYER_BASE', 'https://player.mediadelivery.net/play'), '/'),
        'signed_urls' => filter_var(env('BUNNY_STREAM_SIGNED_URLS', false), FILTER_VALIDATE_BOOL),
        'token_key' => env('BUNNY_STREAM_TOKEN_KEY'),
        'token_security_key' => env('BUNNY_STREAM_TOKEN_SECURITY_KEY'),
        'embed_token_auth_enabled' => filter_var(env('BUNNY_STREAM_TOKEN_AUTH_ENABLED', false), FILTER_VALIDATE_BOOL),
        'embed_url_expiration' => (int) env('BUNNY_STREAM_URL_EXPIRATION', 3600),
        'webhook_secret' => env('BUNNY_STREAM_WEBHOOK_SECRET'),
        'verify_ssl' => filter_var(env('BUNNY_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'upload_timeout' => (int) env('BUNNY_STREAM_UPLOAD_TIMEOUT', env('BUNNY_STREAM_TIMEOUT', 3600)),
        'signed_url_ttl_seconds' => (int) env('BUNNY_STREAM_SIGNED_URL_TTL', 3600),
        'collections_enabled' => filter_var(env('BUNNY_STREAM_COLLECTIONS_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Chat (Tawk.to)
    |--------------------------------------------------------------------------
    */
    'tawk' => [
        'enabled' => env('TAWK_TO_ENABLED', true),
        'embed_url' => env('TAWK_TO_EMBED_URL', 'https://embed.tawk.to/696d342c452bfa197c8f9a84/1jf997k8c'),
    ],

];
