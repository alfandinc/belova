<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'hrd_employee';

    protected $fillable = [
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'alamat',
        'village_id',
        'position',
        'pendidikan',
        'no_hp',
        'tanggal_masuk',
        'status',
        'kontrak_berakhir',
        'masa_pensiun',
        'doc_cv',
        'doc_ktp',
        'doc_kontrak',
        'doc_pendukung',
        'user_id'
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position');
    }

    // public function village()
    // {
    //     return $this->belongsTo(AreaVillage::class, 'village_id');
    // }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
