<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JasaFarmasi extends Model
{
    use HasFactory;

    protected $table = 'erm_jasa_farmasi';
    protected $fillable = ['nama', 'harga', 'keterangan'];
    
    public function billings()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
}