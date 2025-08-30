<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrMasterTunjanganJabatan extends Model
{
    protected $table = 'pr_master_tunjangan_jabatan';
    protected $fillable = ['golongan', 'nominal'];
}
