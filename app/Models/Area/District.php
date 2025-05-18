<?php

namespace App\Models\Area;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'area_districts';
    protected $fillable = ['name', 'regency_id'];

    public function villages()
    {
        return $this->hasMany(Village::class, 'district_id');
    }
}
