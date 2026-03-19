<?php

namespace App\Models\Events;

use App\Models\ERM\Pasien;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lebaran extends Model
{
    use HasFactory;

    protected $table = 'event_lebarans';

    protected $fillable = [
        'nama_pasien',
        'pasien_id',
        'nohp',
        'status',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id', 'id');
    }
}