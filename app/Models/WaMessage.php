<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    use HasFactory;

    protected $table = 'wa_messages';
    protected $fillable = ['session_client_id','direction','from','to','body','message_id','raw','pasien_id'];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }
}
