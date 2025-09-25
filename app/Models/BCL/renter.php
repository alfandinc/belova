<?php

namespace App\Models\BCL;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class renter extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama',
        'alamat',
        'phone',
        'phone2',
        'identitas',
        'no_identitas',
        'kendaraan',
        'nopol',
        'birthday',
    ];
    protected $table = 'bcl_renter';
    protected $primaryKey = 'id';

    public function tr_renter()
    {
        return $this->hasMany(tr_renter::class, 'id_renter');
    }
    public function document()
    {
        return $this->hasMany(renter_document::class, 'id_renter');
    }

    public function current_room()
    {
        // join against actual rooms table name
        return $this->hasOne(tr_renter::class, 'id_renter')
            ->leftjoin('bcl_rooms', 'bcl_tr_renter.room_id', '=', 'bcl_rooms.id')
            ->where('bcl_tr_renter.tgl_mulai', '<=', Carbon::now())
            ->where('bcl_tr_renter.tgl_selesai', '>=', Carbon::now());
    }


}
