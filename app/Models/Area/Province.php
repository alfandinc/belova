<?php

namespace App\Models\Area;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'area_provinces';
    protected $fillable = ['name'];

    public function regencies()
    {
        return $this->hasMany(Regency::class, 'province_id');
    }
}
