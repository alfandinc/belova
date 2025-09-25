<?php

namespace App\Models\BCL;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rooms extends Model
{
    use HasFactory, SoftDeletes;
    // protected $primaryKey = 'id';
    protected $fillable = [
        'room_name',
        'room_category',
        'notes',
    ];
    // protected $cast = [
    //     'tanggal' => 'date:Y-m-d',
    //     'tgl_mulai' => 'date:Y-m-d',
    //     'tgl_selesai' => 'date:Y-m-d'
    // ];
    protected $table = 'bcl_rooms';
    protected $primaryKey = 'id';

    public function category()
    {
        return $this->belongsTo(room_category::class, 'room_category')->withTrashed();
    }

    public function renter()
    {
        return $this->hasOne(tr_renter::class, 'room_id')
            ->leftjoin('bcl_renter', 'bcl_renter.id', '=', 'bcl_tr_renter.id_renter')
            ->where('bcl_tr_renter.tgl_mulai', '<=', Carbon::now()->format('Y-m-d'))
            ->where('bcl_tr_renter.tgl_selesai', '>=', Carbon::now()->format('Y-m-d'));
    }
}
