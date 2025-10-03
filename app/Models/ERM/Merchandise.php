<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    use HasFactory;

    protected $table = 'erm_merchandises';

    protected $fillable = [
        'name', 'description', 'price', 'stock'
    ];

    public function pasienReceipts()
    {
        return $this->hasMany(PasienMerchandise::class, 'merchandise_id');
    }
}
