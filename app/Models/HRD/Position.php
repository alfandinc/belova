<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $table = 'hrd_position';

    protected $fillable = ['name', 'division_id'];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'position');
    }
}
