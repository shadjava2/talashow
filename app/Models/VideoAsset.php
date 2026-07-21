<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'model_type',
        'model_id',
        'type',
        'title',
        'slug',
        'bunny_video_guid',
        'bunny_library_id',
        'bunny_collection_id',
        'bunny_status',
        'bunny_embed_url',
        'bunny_play_url',
        'bunny_thumbnail_url',
        'bunny_hls_url',
        'original_filename',
        'mime_type',
        'file_size',
        'duration',
        'width',
        'height',
        'encode_progress',
        'is_public',
        'visibility',
        'processing_state',
        'meta_json',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'meta_json' => 'array',
        'published_at' => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
