<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    use HasFactory;

    protected $table = 'erm_merchandises';

    protected $fillable = [
        'name', 'description', 'price', 'monthly_limit_stock'
    ];

    public function pasienReceipts()
    {
        return $this->hasMany(PasienMerchandise::class, 'merchandise_id');
    }

    public function kartuStok()
    {
        return $this->hasMany(MerchandiseKartuStok::class, 'merchandise_id');
    }
}
