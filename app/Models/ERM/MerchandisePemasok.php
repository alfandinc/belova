<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class MerchandisePemasok extends Model
{
    protected $table = 'erm_merchandise_pemasok';

    protected $fillable = ['nama', 'no_telp'];
}
