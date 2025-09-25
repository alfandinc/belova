<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room_Category_image extends Model
{
    use HasFactory;
    protected $table = 'bcl_room_category_image';
    protected $fillable = [
        'room_category_id',
        'tag',
        'image',
    ];
    public function category()
    {
        return $this->belongsTo(room_category::class, 'room_category_id', 'id_category');
    }
}
