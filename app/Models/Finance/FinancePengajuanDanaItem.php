<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancePengajuanDanaItem extends Model
{
    use HasFactory;

    protected $table = 'finance_pengajuan_dana_item';

    protected $fillable = [
        'pengajuan_id',
        'employee_id',
        'notes',
        'nama_item',
        'jumlah',
        'harga_satuan',
        // faktur integration fields
        'fakturbeli_id',
        'is_faktur',
        'harga_total_snapshot',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(FinancePengajuanDana::class, 'pengajuan_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'employee_id');
    }

    public function faktur()
    {
        return $this->belongsTo(\App\Models\ERM\FakturBeli::class, 'fakturbeli_id');
    }
}
