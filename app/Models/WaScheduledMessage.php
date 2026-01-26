<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaScheduledMessage extends Model
{
    protected $table = 'wa_scheduled_messages';

    protected $fillable = [
        'client_id',
        'to',
        'message',
        'schedule_at',
        'status',
        'response'
    ];

    protected $dates = ['schedule_at', 'sent_at', 'created_at', 'updated_at'];
}
