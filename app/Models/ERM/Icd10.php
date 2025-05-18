<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Icd10 extends Model
{
    protected $table = 'erm_icd10';

    protected $fillable = [
        'code',
        'description',
        'category',
    ];
}
