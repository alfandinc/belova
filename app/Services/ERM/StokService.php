<?php

namespace App\Services\ERM;

use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use App\Models\ERM\KartuStok;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StokService
{
    /**
     * Menambah stok obat ke gudang tertentu
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param string|null $batch
     * @param string|null $expDate
     * @param string|null $rak
     * @param string|null $lokasi
     * @param float|null $hargaBeli HPP include diskon
     * @param float|null $hargaBeliJual HPP exclude diskon
     * @return ObatStokGudang
     */
    public function tambahStok($obatId, $gudangId, $jumlah, $batch = null, $expDate = null, $rak = null, $lokasi = null, $hargaBeli = null, $hargaBeliJual = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch, $expDate, $rak, $lokasi, $hargaBeli, $hargaBeliJual) {
            $stok = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->first();

            if ($stok) {
                $stok->increment('stok', $jumlah);
                
                // Update harga beli dengan weighted average jika ada harga baru
                if ($hargaBeli !== null) {
                    $oldTotal = $stok->stok - $jumlah; // Stok sebelum ditambah
                    $oldValue = $oldTotal * ($stok->harga_beli ?? 0);
                    $newValue = $jumlah * $hargaBeli;
                    $stok->harga_beli = $stok->stok > 0 ? ($oldValue + $newValue) / $stok->stok : $hargaBeli;
                }
                
                if ($hargaBeliJual !== null) {
                    $oldTotal = $stok->stok - $jumlah; // Stok sebelum ditambah
                    $oldValueJual = $oldTotal * ($stok->harga_beli_jual ?? 0);
                    $newValueJual = $jumlah * $hargaBeliJual;
                    $stok->harga_beli_jual = $stok->stok > 0 ? ($oldValueJual + $newValueJual) / $stok->stok : $hargaBeliJual;
                }
                
                $stok->save();
            } else {
                $stok = ObatStokGudang::create([
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'stok' => $jumlah,
                    'batch' => $batch,
                    'expiration_date' => $expDate ? Carbon::parse($expDate) : null,
                    'rak' => $rak,
                    'lokasi' => $lokasi,
                    'harga_beli' => $hargaBeli,
                    'harga_beli_jual' => $hargaBeliJual
                ]);
            }

            // Catat di kartu stok
            $this->catatKartuStok($obatId, $gudangId, 'masuk', $jumlah, $batch, 'Penambahan stok');

            return $stok;
        });
    }

    /**
     * Mengurangi stok obat dari gudang tertentu
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param string|null $batch
     * @return bool
     * @throws \Exception
     */
    public function kurangiStok($obatId, $gudangId, $jumlah, $batch = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch) {
            $query = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId);

            if ($batch) {
                $query->where('batch', $batch);
            }

            $stok = $query->first();

            if (!$stok || $stok->stok < $jumlah) {
                throw new \Exception('Stok tidak mencukupi');
            }

            $stok->decrement('stok', $jumlah);

            // Catat di kartu stok
            $this->catatKartuStok($obatId, $gudangId, 'keluar', $jumlah, $batch, 'Pengurangan stok');

            return true;
        });
    }

    /**
     * Cek ketersediaan stok
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param string|null $batch
     * @return bool
     */
    public function cekStok($obatId, $gudangId, $jumlah, $batch = null)
    {
        $query = ObatStokGudang::where('obat_id', $obatId)
            ->where('gudang_id', $gudangId);

        if ($batch) {
            $query->where('batch', $batch);
        }

        $stok = $query->first();

        return $stok && $stok->stok >= $jumlah;
    }

    /**
     * Mendapatkan total stok obat dari semua gudang
     *
     * @param int $obatId
     * @return float
     */
    public function getTotalStok($obatId)
    {
        return ObatStokGudang::where('obat_id', $obatId)->sum('stok');
    }

    /**
     * Mendapatkan stok per gudang
     *
     * @param int $obatId
     * @return \Illuminate\Support\Collection
     */
    public function getStokPerGudang($obatId)
    {
        return ObatStokGudang::with('gudang')
            ->where('obat_id', $obatId)
            ->get()
            ->map(function ($item) {
                return [
                    'gudang_id' => $item->gudang_id,
                    'nama_gudang' => $item->gudang->nama,
                    'stok' => $item->stok,
                    'batch' => $item->batch,
                    'expired' => $item->expiration_date,
                    'lokasi' => $item->lokasi
                ];
            });
    }

    /**
     * Mutasi stok antar gudang
     *
     * @param int $obatId
     * @param int $fromGudangId
     * @param int $toGudangId
     * @param float $jumlah
     * @param string|null $batch
     * @return bool
     * @throws \Exception
     */
    public function mutasiStok($obatId, $fromGudangId, $toGudangId, $jumlah, $batch = null)
    {
        return DB::transaction(function () use ($obatId, $fromGudangId, $toGudangId, $jumlah, $batch) {
            // Kurangi stok dari gudang asal
            $this->kurangiStok($obatId, $fromGudangId, $jumlah, $batch);

            // Tambah stok ke gudang tujuan
            $this->tambahStok($obatId, $toGudangId, $jumlah, $batch);

            // Catat di kartu stok untuk kedua gudang
            $this->catatKartuStok($obatId, $fromGudangId, 'keluar', $jumlah, $batch, "Mutasi ke gudang {$toGudangId}");
            $this->catatKartuStok($obatId, $toGudangId, 'masuk', $jumlah, $batch, "Mutasi dari gudang {$fromGudangId}");

            return true;
        });
    }

    /**
     * Update stok fisik (untuk stok opname)
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $stokFisik
     * @param string|null $batch
     * @return ObatStokGudang
     */
    public function updateStokFisik($obatId, $gudangId, $stokFisik, $batch = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $stokFisik, $batch) {
            $stok = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->first();

            if ($stok) {
                $selisih = $stokFisik - $stok->stok;
                $stok->stok = $stokFisik;
                $stok->save();

                // Catat di kartu stok
                $this->catatKartuStok(
                    $obatId, 
                    $gudangId, 
                    $selisih >= 0 ? 'masuk' : 'keluar',
                    abs($selisih),
                    $batch,
                    'Adjustment stok opname'
                );
            }

            return $stok;
        });
    }

    /**
     * Mendapatkan stok yang akan expire dalam x hari
     *
     * @param int $days
     * @return \Illuminate\Support\Collection
     */
    public function getStokAkanExpire($days = 30)
    {
        $date = Carbon::now()->addDays($days);
        
        return ObatStokGudang::with(['obat', 'gudang'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', $date)
            ->whereDate('expiration_date', '>=', Carbon::now())
            ->where('stok', '>', 0)
            ->get();
    }

    /**
     * Mendapatkan stok dibawah minimum
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStokMinimum()
    {
        return ObatStokGudang::with(['obat', 'gudang'])
            ->whereRaw('stok < min_stok')
            ->where('stok', '>=', 0)
            ->get();
    }

    /**
     * Mencatat perubahan stok di kartu stok
     *
     * @param int $obatId
     * @param int $gudangId
     * @param string $tipe
     * @param float $jumlah
     * @param string|null $batch
     * @param string $keterangan
     * @return void
     */
    private function catatKartuStok($obatId, $gudangId, $tipe, $jumlah, $batch, $keterangan, $refType = null, $refId = null)
    {
        // Dapatkan stok terakhir
        $stokAkhir = ObatStokGudang::where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->where('batch', $batch)
            ->value('stok');
        if ($stokAkhir === null) {
            $stokAkhir = 0;
        }

        // Buat record kartu stok
        return KartuStok::create([
            'obat_id' => $obatId,
            'gudang_id' => $gudangId,
            'tanggal' => now(),
            'tipe' => $tipe, // 'masuk' atau 'keluar'
            'qty' => $jumlah,
            'stok_setelah' => $stokAkhir,
            'batch' => $batch,
            'keterangan' => $keterangan,
            'ref_type' => $refType,  // misalnya 'pembelian', 'penjualan', 'mutasi', 'opname'
            'ref_id' => $refId       // ID referensi dari transaksi terkait
        ]);
    }
}
