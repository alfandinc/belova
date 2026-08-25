<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceAkun extends Model
{
    use HasFactory;

    protected $table = 'finance_akun';

    protected $fillable = [
        'parent_id',
        'kode_akun',
        'nama_akun',
        'tipe_akun',
        'level',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function jurnal()
    {
        return $this->hasMany(FinanceJurnal::class, 'akun_id');
    }
}
