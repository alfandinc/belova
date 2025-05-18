<?php

namespace App\Models\Area;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    use HasFactory;

    protected $table = 'area_regencies';
    protected $fillable = ['name', 'province_id'];

    public function districts()
    {
        return $this->hasMany(District::class, 'regency_id');
    }
}
