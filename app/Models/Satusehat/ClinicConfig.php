<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Klinik;

class ClinicConfig extends Model
{
    protected $table = 'satusehat_clinic_configs';
    protected $fillable = [
        'klinik_id', 'auth_url', 'base_url', 'consent_url', 'client_id', 'client_secret', 'organization_id', 'token'
    ];

    protected $casts = [
        'token' => 'string',
        'token_expires_at' => 'datetime'
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }
}
