<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Klinik;

class Location extends Model
{
    protected $table = 'satusehat_locations';

    protected $fillable = [
        'klinik_id','location_id','description','province','city','district','village','rt','rw','line','postal_code','identifier_value','name','latitude','longitude'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }
}
