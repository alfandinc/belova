<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class StokBarang extends Model
{
    use HasFactory;

    protected $table = 'inv_stok_barang';
    protected $fillable = [
        'barang_id',
        'jumlah',
    ];
    
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    /**
     * Adjust stock and record kartu stok via service.
     *
     * @param int $change
     * @param string|null $keterangan
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param int|null $userId
     * @return \App\Models\Inventory\KartuStok
     */
    public static function adjustStock(int $barangId, int $change, ?string $keterangan = null, ?string $referenceType = null, ?int $referenceId = null, ?int $userId = null)
    {
        $svc = new \App\Services\Inventory\StockService();
        return $svc->adjustStock($barangId, $change, $keterangan, $referenceType, $referenceId, $userId);
    }
}
