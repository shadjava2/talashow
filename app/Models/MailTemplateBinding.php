<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailTemplateBinding extends Model
{
    protected $fillable = [
        'event_key',
        'mail_template_id',
    ];

    public function template()
    {
        return $this->belongsTo(MailTemplate::class, 'mail_template_id');
    }
}

