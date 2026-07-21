<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoPlaybackSession extends Model
{
    protected $fillable = [
        'user_id',
        'episode_id',
        'guest_session_key',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'video_lang',
        'session_token',
        'playback_token_hash',
        'started_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
