<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use app\Models\Fin_jurnal;

class tr_renter extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'bcl_tr_renter';
    protected $primaryKey = 'id';
    // protected $casts = [
    //     'tanggal' => 'date:Y-m-d',
    //     'tgl_mulai' => 'date:Y-m-d',
    //     'tgl_selesai' => 'date:Y-m-d'
    // ];
    public function renter()
    {
        return $this->belongsTo(renter::class, 'id_renter');
    }
    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id')->withTrashed();
    }
    public function jurnal()
    {
        return $this->hasMany(Fin_jurnal::class, 'doc_id', 'trans_id')->where('kode_akun', '4-10101');
    }

    public function tambahan()
    {
        return $this->hasMany(tb_extra_rent::class, 'parent_trans', 'trans_id')->with('jurnal');
            // ->where('tgl_mulai', '<=', Carbon::now()->format('Y-m-d'))
            // ->where('tgl_selesai', '>=', Carbon::now()->format('Y-m-d'));
    }
}
