<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionDivision extends Model
{
    use HasFactory;

    protected $table = 'hrd_position_division';

    protected $fillable = [
        'position_id',
        'division_id',
        'parent_position_id',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function parentPosition()
    {
        return $this->belongsTo(Position::class, 'parent_position_id');
    }
}