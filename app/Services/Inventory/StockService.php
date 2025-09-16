<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StokBarang;
use App\Models\Inventory\KartuStok;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Adjust stock for a barang and record kartu stok entry.
     *
     * @param int $barangId
     * @param int $change positive for masuk, negative for keluar
     * @param string|null $keterangan
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param int|null $userId
     * @return KartuStok
     */
    public function adjustStock(int $barangId, int $change, ?string $keterangan = null, ?string $referenceType = null, ?int $referenceId = null, ?int $userId = null)
    {
        return DB::transaction(function () use ($barangId, $change, $keterangan, $referenceType, $referenceId, $userId) {
            $stok = StokBarang::firstOrCreate(
                ['barang_id' => $barangId],
                ['jumlah' => 0]
            );

            $stokAwal = (int) $stok->jumlah;
            $stokAkhir = $stokAwal + $change;

            // update stok
            $stok->jumlah = $stokAkhir;
            $stok->save();

            // create kartu stok record
            $kartu = KartuStok::create([
                'barang_id' => $barangId,
                'stok_awal' => $stokAwal,
                'stok_masuk' => $change > 0 ? $change : 0,
                'stok_keluar' => $change < 0 ? abs($change) : 0,
                'stok_akhir' => $stokAkhir,
                'keterangan' => $keterangan,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $userId,
                'tanggal' => Carbon::now(),
            ]);

            return $kartu;
        });
    }
}
