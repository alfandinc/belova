<?php

namespace App\Services\ERM;

use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use App\Models\ERM\KartuStok;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StokService {
    /**
     * Hitung nilai stok gudang tertentu (stok * hpp_jual per obat di gudang)
     * @param int $gudangId
     * @return float
     */
    public function getNilaiStokGudang($gudangId)
    {
        return ObatStokGudang::with('obat')
            ->where('gudang_id', $gudangId)
            ->get()
            ->sum(function ($item) {
                // Use master cost (`hpp`) for inventory valuation
                $hpp = $item->obat ? ($item->obat->hpp ?? 0) : 0;
                return ($item->stok ?? 0) * $hpp;
            });
    }

    /**
     * Hitung nilai stok keseluruhan (stok * hpp_jual per obat di semua gudang)
     * @return float
     */
    public function getNilaiStokKeseluruhan()
    {
        return ObatStokGudang::with('obat')
            ->get()
            ->sum(function ($item) {
                // Use master cost (`hpp`) for inventory valuation
                $hpp = $item->obat ? ($item->obat->hpp ?? 0) : 0;
                return ($item->stok ?? 0) * $hpp;
            });
    }
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
    public function tambahStok($obatId, $gudangId, $jumlah, $batch = null, $expDate = null, $rak = null, $lokasi = null, $hargaBeli = null, $hargaBeliJual = null, $refType = null, $refId = null, $keterangan = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch, $expDate, $rak, $lokasi, $hargaBeli, $hargaBeliJual, $refType, $refId, $keterangan) {
            // Ensure numeric values are properly cast
            $jumlah = (float) $jumlah;
            $hargaBeli = $hargaBeli !== null ? (float) $hargaBeli : null;
            $hargaBeliJual = $hargaBeliJual !== null ? (float) $hargaBeliJual : null;
            
            $stok = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->lockForUpdate()
                ->first();

            if ($stok) {
                // Use precise float arithmetic and assign directly to avoid implicit integer casting
                $current = (float) $stok->stok;
                $stok->stok = $current + (float) $jumlah;
                $stok->save();
            } else {
                $stok = ObatStokGudang::create([
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'stok' => (float) $jumlah,
                    'batch' => $batch,
                    'expiration_date' => $expDate ? Carbon::parse($expDate) : null,
                    'rak' => $rak,
                    'lokasi' => $lokasi
                ]);
            }

            // Update HPP di master obat jika ada harga beli baru (hanya untuk pembelian)
            // Use the price that excludes discounts (`hargaBeliJual`) when available.
            // Set master `hpp` and `hpp_jual` directly to the new price (no averaging).
            if ($hargaBeli !== null || $hargaBeliJual !== null) {
                $obat = Obat::find($obatId);
                if ($obat) {
                    if ($hargaBeliJual !== null) {
                        $priceNoDiscount = (float) $hargaBeliJual;
                        $obat->hpp = $priceNoDiscount;
                        $obat->hpp_jual = $priceNoDiscount;
                    } else {
                        // Fallback: only hargaBeli provided (may include discount) — set hpp to that value
                        $obat->hpp = (float) $hargaBeli;
                    }
                    $obat->save();
                }
            }

            // Catat di kartu stok dengan referensi
            $this->catatKartuStok(
                $obatId, 
                $gudangId, 
                'masuk', 
                $jumlah, 
                $batch, 
                $keterangan ?: 'Penambahan stok',
                $refType,
                $refId
            );

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
     * @param string|null $refType Jenis referensi (pembelian, penjualan, mutasi, opname)
     * @param int|null $refId ID referensi transaksi
     * @param string|null $keterangan Keterangan tambahan
     * @return bool
     * @throws \Exception
     */
    public function kurangiStok($obatId, $gudangId, $jumlah, $batch = null, $refType = null, $refId = null, $keterangan = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch, $refType, $refId, $keterangan) {
            // Debug: log incoming parameters and types to diagnose rounding/truncation issues
            try {
                \Illuminate\Support\Facades\Log::info('StokService::kurangiStok called', [
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'batch' => $batch,
                    'jumlah' => $jumlah,
                    'jumlah_type' => gettype($jumlah),
                    'refType' => $refType,
                    'refId' => $refId,
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            $query = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId);

            if ($batch) {
                $query->where('batch', $batch);
            }

            // Lock the stock row for update to avoid race conditions when modifying stok
            $stok = $query->lockForUpdate()->first();

            if (!$stok || (float)$stok->stok < (float)$jumlah) {
                throw new \Exception('Stok tidak mencukupi');
            }

            // Perform precise subtraction and save to ensure decimals preserved
            $current = (float) $stok->stok;
            $stok->stok = $current - (float)$jumlah;
            $stok->save();

            // Catat di kartu stok dengan referensi
            $this->catatKartuStok(
                $obatId, 
                $gudangId, 
                'keluar', 
                $jumlah, 
                $batch, 
                $keterangan ?: 'Pengurangan stok',
                $refType,
                $refId
            );

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
     * @param int|null $mutasiId ID transaksi mutasi
     * @return bool
     * @throws \Exception
     */
    public function mutasiStok($obatId, $fromGudangId, $toGudangId, $jumlah, $batch = null, $mutasiId = null)
    {
        return DB::transaction(function () use ($obatId, $fromGudangId, $toGudangId, $jumlah, $batch, $mutasiId) {
            // Get gudang names for better description
            $fromGudang = Gudang::find($fromGudangId);
            $toGudang = Gudang::find($toGudangId);
            
            $fromGudangName = $fromGudang ? $fromGudang->nama : "Gudang {$fromGudangId}";
            $toGudangName = $toGudang ? $toGudang->nama : "Gudang {$toGudangId}";

            // Kurangi stok dari gudang asal dengan referensi mutasi
            $this->kurangiStok(
                $obatId, 
                $fromGudangId, 
                $jumlah, 
                $batch,
                'mutasi_gudang',
                $mutasiId,
                "Mutasi ke {$toGudangName}"
            );

            // Tambah stok ke gudang tujuan dengan referensi mutasi
            $this->tambahStok(
                $obatId, 
                $toGudangId, 
                $jumlah, 
                $batch,
                null, // expDate
                null, // rak
                null, // lokasi  
                null, // hargaBeli
                null, // hargaBeliJual
                'mutasi_gudang',
                $mutasiId,
                "Mutasi dari {$fromGudangName}"
            );

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
     * @param int|null $stokOpnameId ID transaksi stok opname
     * @return ObatStokGudang
     */
    public function updateStokFisik($obatId, $gudangId, $stokFisik, $batch = null, $stokOpnameId = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $stokFisik, $batch, $stokOpnameId) {
            $stok = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->first();

            if ($stok) {
                $selisih = $stokFisik - $stok->stok;
                $stok->stok = $stokFisik;
                $stok->save();

                // Catat di kartu stok dengan referensi stok opname
                $this->catatKartuStok(
                    $obatId, 
                    $gudangId, 
                    $selisih >= 0 ? 'masuk' : 'keluar',
                    abs($selisih),
                    $batch,
                    'Adjustment stok opname',
                    'stok_opname',
                    $stokOpnameId
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
            'qty' => (float) $jumlah,
            'stok_setelah' => (float) $stokAkhir,
            'batch' => $batch,
            'keterangan' => $keterangan,
            'ref_type' => $refType,  // misalnya 'pembelian', 'penjualan', 'mutasi', 'opname'
            'ref_id' => $refId       // ID referensi dari transaksi terkait
        ]);
    }

    /**
     * Khusus untuk obat masuk via faktur pembelian
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param int $fakturId
     * @param string $noFaktur
     * @param string|null $batch
     * @param string|null $expDate
     * @param float|null $hargaBeli
     * @param float|null $hargaBeliJual
     * @param string|null $namaPemasok
     * @return ObatStokGudang
     */
    public function masukViaFaktur($obatId, $gudangId, $jumlah, $fakturId, $noFaktur, $batch = null, $expDate = null, $hargaBeli = null, $hargaBeliJual = null, $namaPemasok = null)
    {
        $keterangan = "Pembelian via Faktur: {$noFaktur}";
        if ($namaPemasok) {
            $keterangan .= " dari {$namaPemasok}";
        }
        
        return $this->tambahStok(
            $obatId, 
            $gudangId, 
            $jumlah, 
            $batch, 
            $expDate, 
            null, // rak
            null, // lokasi
            $hargaBeli,
            $hargaBeliJual,
            'faktur_pembelian', 
            $fakturId,
            $keterangan
        );
    }

    /**
     * Khusus untuk obat keluar via invoice penjualan
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param int $invoiceId
     * @param string $invoiceNumber
     * @param string|null $batch
     * @return bool
     */
    public function keluarViaInvoice($obatId, $gudangId, $jumlah, $invoiceId, $invoiceNumber, $batch = null)
    {
        return $this->kurangiStok(
            $obatId, 
            $gudangId, 
            $jumlah, 
            $batch,
            'invoice_penjualan', 
            $invoiceId,
            "Penjualan via Invoice: {$invoiceNumber}"
        );
    }

    /**
     * Khusus untuk mutasi dengan ID transaksi mutasi
     *
     * @param int $obatId
     * @param int $fromGudangId
     * @param int $toGudangId
     * @param float $jumlah
     * @param int $mutasiId
     * @param string $noMutasi
     * @param string|null $batch
     * @return bool
     */
    public function mutasiViaTransaksi($obatId, $fromGudangId, $toGudangId, $jumlah, $mutasiId, $noMutasi, $batch = null)
    {
        return $this->mutasiStok($obatId, $fromGudangId, $toGudangId, $jumlah, $batch, $mutasiId);
    }

    /**
     * Khusus untuk stok opname dengan ID transaksi
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $stokFisik
     * @param int $stokOpnameId
     * @param string $noStokOpname
     * @param string|null $batch
     * @return ObatStokGudang
     */
    public function stokOpnameViaTransaksi($obatId, $gudangId, $stokFisik, $stokOpnameId, $noStokOpname, $batch = null)
    {
        return $this->updateStokFisik($obatId, $gudangId, $stokFisik, $batch, $stokOpnameId);
    }

    /**
     * Khusus untuk retur pembelian - menambah stok kembali
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param int $returId
     * @param string $noRetur
     * @param string|null $batch
     * @param float|null $hargaBeli
     * @param float|null $hargaBeliJual
     * @return ObatStokGudang
     */
    public function returPembelianViaTransaksi($obatId, $gudangId, $jumlah, $returId, $noRetur, $batch = null, $hargaBeli = null, $hargaBeliJual = null)
    {
        $keterangan = "Retur Pembelian - {$noRetur}";
        
        return $this->tambahStok(
            $obatId,
            $gudangId,
            $jumlah,
            $batch,
            null, // expDate
            null, // rak
            null, // lokasi
            $hargaBeli,
            $hargaBeliJual,
            'retur_pembelian', // refType
            $returId, // refId
            $keterangan
        );
    }
}
