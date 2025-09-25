<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class room_category extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'bcl_room_category';
    protected $guarded = ['id_category'];
    protected $primaryKey = 'id_category';
    public function rooms()
    {
        return $this->hasMany(Rooms::class, 'id_category');
    }

    public function images()
    {
        return $this->hasMany(Room_Category_image::class, 'room_category_id');
    }
}
