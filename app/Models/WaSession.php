<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaSession extends Model
{
    use HasFactory;

    protected $table = 'wa_sessions';
    protected $fillable = ['client_id', 'label'];
}
