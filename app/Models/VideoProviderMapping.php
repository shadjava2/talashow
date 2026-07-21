<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProviderMapping extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'video_id',
        'video_lang',
        'content_type',
        'content_id',
        'source_provider',
        'source_asset_id',
        'source_playback_url',
        'target_provider',
        'target_library_id',
        'target_video_guid',
        'target_cdn_hostname',
        'target_hls_url',
        'migration_status',
        'migration_error',
        'last_checked_at',
        'migrated_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'migrated_at' => 'datetime',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class, 'video_id');
    }
}
