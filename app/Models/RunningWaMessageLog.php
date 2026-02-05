<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RunningWaMessageLog extends Model
{
    protected $table = 'running_wa_message_logs';

    protected $fillable = [
        'peserta_id', 'scheduled_message_id', 'client_id', 'direction', 'to', 'body', 'response', 'message_id', 'raw'
    ];
}
