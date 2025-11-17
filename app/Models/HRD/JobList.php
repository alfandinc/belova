<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobList extends Model
{
    use HasFactory;

    protected $table = 'hrd_joblists';

    protected $fillable = [
        'title', 'description', 'status', 'priority', 'division_id', 'due_date', 'created_by'
    ];

    public function division()
    {
        return $this->belongsTo(\App\Models\HRD\Division::class, 'division_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
