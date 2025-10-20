<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppBotFlow extends Model
{
    protected $table = 'whatsapp_bot_flows';
    protected $guarded = ['id'];
    protected $casts = [
        'triggers' => 'array',
        'choices' => 'array',
    ];
}
