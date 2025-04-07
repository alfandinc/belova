<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class MetodeBayar extends Model
{
    protected $table = 'erm_metode_bayar';

    protected $fillable = ['nama'];

    public function visitations()
    {
        return $this->hasMany(Visitation::class, 'metode_bayar_id');
    }
}
