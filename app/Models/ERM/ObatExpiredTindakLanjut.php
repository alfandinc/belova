<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObatExpiredTindakLanjut extends Model
{
    use HasFactory;

    protected $table = 'erm_obat_expired_tindak_lanjut';

    const UPDATED_AT = null;

    // Diretur waits for the Retur Pembelian approval before stok leaves the gudang expired
    const STATUS_MENUNGGU_RETUR = 'menunggu_retur';
    const STATUS_SELESAI = 'selesai';
    const STATUS_BATAL = 'batal';

    protected $fillable = [
        'obat_id',
        'obat_stok_gudang_id',
        'jumlah',
        'expiration_date',
        'tindak_lanjut',
        'status',
        'nomor_dokumen',
        'tanggal',
        'metode',
        'saksi_1',
        'saksi_2',
        'fakturretur_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'expiration_date' => 'date',
        'tanggal' => 'date',
        'created_at' => 'datetime',
    ];

    public function fakturRetur()
    {
        return $this->belongsTo(FakturRetur::class, 'fakturretur_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function stokGudang()
    {
        // Keep batch / gudang readable on documents even if the stok row is later soft-deleted
        return $this->belongsTo(ObatStokGudang::class, 'obat_stok_gudang_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}