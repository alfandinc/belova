<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    protected $table = 'erm_konsultasi';
    protected $fillable = ['nama', 'harga'];

    public function billing()
    {
        return $this->morphOne(Billing::class, 'billable');
    }
}
