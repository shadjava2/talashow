<?php

return [

    'enabled' => env('CLOUDFLARE_SECURITY_ENABLED', false),

    /*
    | Seuil bot (1 = très probable humain, valeur basse = bot selon doc CF).
    | Si le header est absent, aucun blocage.
    */
    'bot_score_warn' => (int) env('CLOUDFLARE_BOT_SCORE_WARN', 30),

    'log_threats' => env('CLOUDFLARE_LOG_THREATS', true),

    'block_low_bot_score' => env('CLOUDFLARE_BLOCK_LOW_BOT', false),

];
