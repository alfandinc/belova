<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'hrd_division';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'hrd_position_division', 'division_id', 'position_id')
            ->withPivot('parent_position_id')
            ->withTimestamps();
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'division_id');
    }

    public function manager()
    {
        return $this->employees()
            ->whereHas('user', function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['manager', 'Manager']);
                });
            })
            ->first();
    }

    public function pengajuanDanas()
    {
        return $this->hasMany(\App\Models\Finance\FinancePengajuanDana::class, 'division_id');
    }
}
