<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObatHibahItem extends Model
{
    use HasFactory;

    protected $table = 'erm_obat_hibah_items';

    protected $fillable = [
        'obat_hibah_id',
        'obat_id',
        'gudang_id',
        'qty',
        'batch',
        'expiration_date',
    ];

    public function hibah()
    {
        return $this->belongsTo(ObatHibah::class, 'obat_hibah_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}