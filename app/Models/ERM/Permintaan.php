<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    use HasFactory;

    protected $table = 'erm_permintaan';

    protected $fillable = [
        'no_permintaan',
        'request_date',
        'approved_by',
        'approved_date',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(PermintaanItem::class, 'permintaan_id');
    }
}
