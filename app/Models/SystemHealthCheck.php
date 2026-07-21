<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    protected $table = 'system_health_checks';

    protected $fillable = [
        'check_key',
        'status',
        'payload',
        'checked_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'checked_at' => 'datetime',
    ];
}
