<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RunningWaScheduledMessage extends Model
{
    protected $table = 'running_wa_scheduled_messages';

    protected $fillable = [
        'peserta_id', 'client_id', 'to', 'message', 'schedule_at', 'status', 'response', 'sent_at', 'image_path'
    ];

    // allow setting an image_path if generated and stored on disk
    protected $casts = [
        'schedule_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function getImagePathAttribute($value)
    {
        return $value;
    }

    protected $dates = ['schedule_at', 'sent_at'];
}
