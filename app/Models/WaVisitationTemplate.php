<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaVisitationTemplate extends Model
{
    protected $table = 'wa_visitation_templates';

    protected $fillable = [
        'wa_session_id',
        'klinik_id',
        'template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(WaSession::class, 'wa_session_id');
    }

    public function klinik()
    {
        return $this->belongsTo(\App\Models\ERM\Klinik::class, 'klinik_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}