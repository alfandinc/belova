<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'hrd_division';

    protected $fillable = ['name', 'description'];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'division');
    }

    public function manager()
    {
        return $this->employees()
            ->whereHas('user', function ($query) {
                $query->role('manager');
            })
            ->first();
    }
}
