<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrMasterBenefit extends Model
{
    protected $table = 'pr_master_benefit';
    protected $fillable = ['nama_benefit', 'nominal'];
}
