<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $table = 'hrd_position';

    protected $fillable = ['name', 'description', 'division_id', 'parent_id'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'hrd_employee_position')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
