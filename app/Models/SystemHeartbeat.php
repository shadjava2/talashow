<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHeartbeat extends Model
{
    protected $table = 'system_heartbeats';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'beat_at',
    ];

    protected $casts = [
        'beat_at' => 'datetime',
    ];
}
