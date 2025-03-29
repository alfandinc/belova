<?php

namespace App\Models\Area;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    use HasFactory;

    protected $table = 'area_villages';
    protected $fillable = ['name', 'district_id'];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
