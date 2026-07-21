<?php

return [

    'default_provider' => env('VIDEO_PROVIDER', 'bunny'),

    'fallback_provider' => env('VIDEO_FALLBACK_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | Lecteur public
    |--------------------------------------------------------------------------
    | hls : Video.js + HLS (playlist.m3u8 Bunny)
    | bunny_embed : iframe Bunny — URL « play » (défaut) ou « embed » selon bunny_iframe_url_style
    */
    'playback_driver' => env('VIDEO_PLAYBACK_DRIVER', 'bunny_embed'),

    /*
    | play  : https://player.mediadelivery.net/play/{libraryId}/{videoId} (UI Bunny standard)
    | embed : https://iframe.mediadelivery.net/embed/{libraryId}/{videoId}
    */
    'bunny_iframe_url_style' => env('BUNNY_STREAM_IFRAME_URL_STYLE', 'play'),

    /*
    |--------------------------------------------------------------------------
    | Migration batch / fichiers sources locaux (optionnel)
    |--------------------------------------------------------------------------
    | La commande video:migrate-cloudflare-to-bunny attend des MP4 locaux :
    |   {VIDEO_MIGRATION_LOCAL_BASE}/{clé}.mp4
    | La clé est dérivée de l’URL (UID 32 car. ex. ancien Stream, ou nom de fichier sans extension).
    */
    'migration_local_base' => env('VIDEO_MIGRATION_LOCAL_BASE'),

];
