<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrMasterPotongan extends Model
{
    protected $table = 'pr_master_potongan';
    protected $fillable = ['nama_potongan', 'nominal'];
}
