<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';
    protected $fillable = [
        'inventory_number',
        'name',
        'condition',
        'quantity',
        'unit_price',
        'book_value',
        'purchase_year',
        'note',
        'initial_depreciation',
        'annual_depreciation',
        'accumulated_depreciation',
        'residual_value',
    ];
}
