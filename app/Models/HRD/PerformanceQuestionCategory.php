<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceQuestionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    public function questions()
    {
        return $this->hasMany(PerformanceQuestion::class, 'category_id');
    }
}
