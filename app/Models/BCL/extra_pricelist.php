<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class extra_pricelist extends Model
{
    use HasFactory;
    protected $table = 'bcl_extra_pricelist';
    protected $guarded = ['id'];
}
