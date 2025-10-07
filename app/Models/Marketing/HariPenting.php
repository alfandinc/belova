<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariPenting extends Model
{
    use HasFactory;

    protected $table = 'marketing_hari_pentings';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'description',
        'color',
        'all_day',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'all_day' => 'boolean',
    ];
}
