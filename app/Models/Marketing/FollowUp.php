<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'marketing_follow_ups';

    protected $fillable = [
        'pasien_id',
        'kategori',
        'sales_id',
        'status_respon',
        'bukti_respon',
        'rencana_tindak_lanjut',
        'status_booking',
        'catatan',
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }

    public function sales()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'sales_id');
    }
}
