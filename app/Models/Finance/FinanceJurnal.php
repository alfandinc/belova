<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Models\Finance\FinanceAkun;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceJurnal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'finance_jurnal';

    protected $fillable = [
        'no_jurnal',
        'tanggal',
        'akun_id',
        'debet',
        'kredit',
        'keterangan',
        'ref_id',
        'user_id',
        'pos',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'debet' => 'decimal:2',
        'kredit' => 'decimal:2',
    ];

    public function akun()
    {
        return $this->belongsTo(FinanceAkun::class, 'akun_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
