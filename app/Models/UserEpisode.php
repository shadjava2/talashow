<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEpisode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'episode_id',
        'is_unlocked',
        'unlock_method',
        'unlocked_until',
        'watch_progress',
        'is_completed',
        'watched_at',
    ];

    protected $casts = [
        'is_unlocked' => 'boolean',
        'is_completed' => 'boolean',
        'watched_at' => 'datetime',
        'unlocked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
