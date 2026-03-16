<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\WaVisitationTemplate;

class WaSession extends Model
{
    use HasFactory;

    protected $table = 'wa_sessions';
    protected $fillable = ['client_id', 'label'];

    public function visitationTemplate()
    {
        return $this->hasOne(WaVisitationTemplate::class, 'wa_session_id');
    }
}
