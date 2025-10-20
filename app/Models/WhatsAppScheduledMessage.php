<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppScheduledMessage extends Model
{
    protected $table = 'whatsapp_scheduled_messages';
    protected $guarded = ['id'];
    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'sent' => 'boolean',
        'failed' => 'boolean',
    ];
}
