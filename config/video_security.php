<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gate applicatif (URL /playback/gate/{token})
    |--------------------------------------------------------------------------
    | Si true : l’iframe pointe vers une URL locale qui vérifie la session
    | Talashow puis redirige vers Bunny avec TTL court. Si false : comportement
    | historique (URL Bunny directe dans le HTML).
    */
    'playback_gate_enabled' => env('VIDEO_SECURITY_PLAYBACK_GATE', false),

    /*
    |--------------------------------------------------------------------------
    | Forcer un TTL court côté Bunny (iframe / HLS) lorsque le token Bunny est activé
    |--------------------------------------------------------------------------
    | S’applique aux appels generateSignedPlayerUrl / maybeSignHlsUrl via le résolveur.
    | Ignoré si le gate est désactivé et harden_bunny_ttl est false.
    */
    'signed_urls_enabled' => env('VIDEO_SECURITY_SIGNED_ENABLED', true),

    'harden_bunny_ttl' => env('VIDEO_SECURITY_HARDEN_BUNNY_TTL', true),

    'token_expiration_seconds' => (int) env('VIDEO_SECURITY_TOKEN_TTL', 120),

    'playback_session_ttl_minutes' => (int) env('VIDEO_SECURITY_SESSION_TTL', 360),

    'max_concurrent_streams' => (int) env('VIDEO_SECURITY_MAX_CONCURRENT', 2),

    'max_devices' => (int) env('VIDEO_SECURITY_MAX_DEVICES', 5),

    'allowed_referrers' => array_values(array_filter(array_map('trim', explode(',', (string) env('VIDEO_SECURITY_ALLOWED_REFERRERS', ''))))),

    'geo_blocking_future' => env('VIDEO_SECURITY_GEO_BLOCK', false),

    'playback_audit_enabled' => env('VIDEO_SECURITY_AUDIT', true),

    'require_login_for_playback' => env('VIDEO_SECURITY_REQUIRE_LOGIN', false),

    'max_distinct_ips_per_day' => (int) env('VIDEO_SECURITY_MAX_IPS_DAY', 12),

    'shadow_ban_threshold' => (int) env('VIDEO_SECURITY_SHADOW_THRESHOLD', 80),

];
