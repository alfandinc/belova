<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'bcl_inventories';

    public function room()
    {

        return $this->belongsTo(Rooms::class, 'assigned_to', 'id');
    }
}
