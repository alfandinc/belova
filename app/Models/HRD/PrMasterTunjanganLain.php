<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrMasterTunjanganLain extends Model
{
    protected $table = 'pr_master_tunjangan_lain';
    protected $fillable = ['nama_tunjangan', 'nominal'];
}
