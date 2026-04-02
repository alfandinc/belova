<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraBedAssignment extends Model
{
    use HasFactory;

    protected $table = 'bcl_extra_bed_assignments';

    protected $fillable = [
        'extra_bed_asset_id',
        'extra_rent_id',
        'assigned_from',
        'assigned_until',
    ];

    protected $casts = [
        'assigned_from' => 'date:Y-m-d',
        'assigned_until' => 'date:Y-m-d',
    ];

    public function asset()
    {
        return $this->belongsTo(ExtraBedAsset::class, 'extra_bed_asset_id');
    }

    public function extraRent()
    {
        return $this->belongsTo(tb_extra_rent::class, 'extra_rent_id');
    }
}