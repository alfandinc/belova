<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Model;

/** Check-in / check-out inspection of a room: inventory condition, keys, meter, photo. */
class RoomInspection extends Model
{
    protected $table = 'bcl_room_inspections';
    protected $guarded = ['id'];
    protected $casts = ['items' => 'array', 'tanggal' => 'date:Y-m-d'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
