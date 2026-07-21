<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoWebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event_name',
        'payload_json',
        'headers_json',
        'processed',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'headers_json' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
