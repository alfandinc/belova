<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'erm_transaction';
    protected $fillable = ['visitation_id', 'transaksible_id', 'transaksible_type', 'jumlah', 'keterangan'];

    public function transaksible()
    {
        return $this->morphTo();
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
