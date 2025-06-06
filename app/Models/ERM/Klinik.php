<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Klinik extends Model
{
    protected $table = 'erm_klinik';
    protected $fillable = ['nama'];

    public function visitation()
    {
        return $this->hasMany(Visitation::class, 'klinik_id');
    }
}
