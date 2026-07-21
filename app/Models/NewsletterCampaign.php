<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'headline',
        'content_html',
        'content_hash',
        'status',
        'last_subscriber_id',
        'sent_count',
        'failed_count',
        'started_at',
        'finished_at',
        'last_error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

