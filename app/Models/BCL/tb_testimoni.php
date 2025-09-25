<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_testimoni extends Model
{
    use HasFactory;
    protected $table = 'bcl_testimoni';
    protected $guarded = ['id'];
}
