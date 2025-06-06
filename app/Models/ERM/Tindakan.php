<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    protected $table = 'erm_tindakan';
    protected $fillable = ['nama', 'deskripsi', 'harga'];

    public function paketTindakan()
    {
        return $this->belongsToMany(PaketTindakan::class, 'erm_paket_tindakan_detail');
    }

    public function informConsent()
    {
        return $this->hasMany(InformConsent::class);
    }

    public function billing()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
}
