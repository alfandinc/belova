<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'hrd_shifts';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'shift_id');
    }
}
