<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class HasilLis extends Model
{
    protected $table = 'erm_hasil_lis';
    public $timestamps = false;
    
    protected $fillable = [
        'visitation_id',
        'kode',
        'kode_lis',
        'header',
        'sub_header',
        'nama_test',
        'hasil',
        'flag',
        'metode',
        'nilai_rujukan',
        'satuan',
   
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
