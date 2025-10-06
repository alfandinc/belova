<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomWifi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bcl_room_wifi';
    protected $primaryKey = 'id';

    protected $fillable = [
        'room_id',
        'ssid',
        'password',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id')->withTrashed();
    }
}
