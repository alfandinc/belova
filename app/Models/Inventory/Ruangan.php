<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'inv_ruangan';
    protected $fillable = [
        'name',
        'gedung_id',
        'description',
    ];
}
