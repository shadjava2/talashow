<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'series_id',
        'first_played_at',
        'last_played_at',
        'play_count',
    ];

    protected $casts = [
        'first_played_at' => 'datetime',
        'last_played_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}

