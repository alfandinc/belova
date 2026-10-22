<?php

namespace App\Services\ERM;

use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\KartuStok;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StokService {
    /**
     * Hitung nilai stok gudang tertentu (stok * hpp per obat di gudang)
     * @param int $gudangId
     * @return float
     */
    public function getNilaiStokGudang($gudangId)
    {
        return $this->nilaiStokQuery()->where('s.gudang_id', $gudangId)->value('nilai') ?? 0;
    }

    /**
     * SUM(stok × master HPP) over active obat, computed in SQL instead of loading every stok row.
     */
    private function nilaiStokQuery()
    {
        return DB::table('erm_obat_stok_gudang as s')
            ->join('erm_obat as o', 'o.id', '=', 's.obat_id')
            ->whereNull('s.deleted_at')
            ->where('o.status_aktif', 1)
            ->selectRaw('COALESCE(SUM(s.stok * COALESCE(o.hpp, 0)), 0) as nilai');
    }

    /**
     * Hitung nilai stok keseluruhan (stok * hpp per obat di semua gudang)
     * @return float
     */
    public function getNilaiStokKeseluruhan()
    {
        return $this->nilaiStokQuery()->value('nilai') ?? 0;
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
     * @param float|null $hargaBeli harga per unit after diskon, excl PPN (fallback only)
     * @param float|null $hargaBeliJual HNA: harga per unit before diskon + ongkir share, excl PPN
     * @return ObatStokGudang
     */
    public function tambahStok($obatId, $gudangId, $jumlah, $batch = null, $expDate = null, $rak = null, $lokasi = null, $hargaBeli = null, $hargaBeliJual = null, $refType = null, $refId = null, $keterangan = null, $tanggal = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch, $expDate, $rak, $lokasi, $hargaBeli, $hargaBeliJual, $refType, $refId, $keterangan, $tanggal) {
            // Ensure numeric values are properly cast
            $jumlah = (float) $jumlah;
            if ($jumlah <= 0) {
                throw new \InvalidArgumentException('Jumlah stok masuk harus lebih dari 0.');
            }
            $hargaBeli = $hargaBeli !== null ? (float) $hargaBeli : null;
            $hargaBeliJual = $hargaBeliJual !== null ? (float) $hargaBeliJual : null;
            
            // Include soft-deleted rows: the unique key (obat_id, gudang_id, batch) also covers them,
            // so creating a new row for a previously deleted batch would fail.
            $stok = ObatStokGudang::withTrashed()
                ->where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->lockForUpdate()
                ->first();

            if ($stok && $stok->trashed()) {
                // A deleted row counts as empty; never revive its old quantity
                $stok->restore();
                $stok->stok = 0;
                if ($expDate) {
                    $stok->expiration_date = Carbon::parse($expDate);
                }
            }

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

            // Update HNA (harga beli + ongkir, tanpa PPN) di master obat jika ada harga beli baru (hanya pembelian);
            // the Obat model sets HPP = HNA + PPN. Use the price before discounts (`hargaBeliJual`) when available.
            // No averaging. Zero-price lines (bonus/free goods) never overwrite it.
            $hargaMaster = (float) ($hargaBeliJual ?? $hargaBeli ?? 0);
            if ($hargaMaster > 0) {
                $obat = Obat::withInactive()->find($obatId);
                if ($obat) {
                    $obat->hna = round($hargaMaster, 2);
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
                $refId,
                $tanggal
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
    public function kurangiStok($obatId, $gudangId, $jumlah, $batch = null, $refType = null, $refId = null, $keterangan = null, $tanggal = null)
    {
        return DB::transaction(function () use ($obatId, $gudangId, $jumlah, $batch, $refType, $refId, $keterangan, $tanggal) {
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

            if ((float) $jumlah <= 0) {
                throw new \InvalidArgumentException('Jumlah stok keluar harus lebih dari 0.');
            }

            // Always the exact batch row: a NULL batch means the row without a batch name,
            // never "any row" (that used to take a random batch of the obat)
            $stok = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('batch', $batch)
                ->lockForUpdate()
                ->first();

            if (!$stok || round((float) $stok->stok, 4) < round((float) $jumlah, 4)) {
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
                $refId,
                $tanggal
            );

            return true;
        });
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
    private function catatKartuStok($obatId, $gudangId, $tipe, $jumlah, $batch, $keterangan, $refType = null, $refId = null, $tanggal = null)
    {
        // Dapatkan stok terakhir
        $stokAkhir = ObatStokGudang::where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->where('batch', $batch)
            ->value('stok');
        if ($stokAkhir === null) {
            $stokAkhir = 0;
        }

        $tanggalTransaksi = $tanggal ? Carbon::parse($tanggal) : now();

        // Buat record kartu stok, sertakan user_id jika tersedia
        return KartuStok::create([
            'obat_id' => $obatId,
            'gudang_id' => $gudangId,
            'tanggal' => $tanggalTransaksi,
            'tipe' => $tipe, // 'masuk' atau 'keluar'
            'qty' => (float) $jumlah,
            'stok_setelah' => (float) $stokAkhir,
            'batch' => $batch,
            'keterangan' => $keterangan,
            'ref_type' => $refType,  // misalnya 'pembelian', 'penjualan', 'mutasi', 'opname'
            'ref_id' => $refId,      // ID referensi dari transaksi terkait
            'user_id' => Auth::id()
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
     * Khusus untuk obat masuk via hibah tanpa perubahan HPP.
     *
     * @param int $obatId
     * @param int $gudangId
     * @param float $jumlah
     * @param int $hibahId
     * @param string $nomorHibah
     * @param string|null $batch
     * @param string|null $expDate
     * @param string|null $sumber
     * @param string|null $catatan
     * @return ObatStokGudang
     */
    public function masukViaHibah($obatId, $gudangId, $jumlah, $hibahId, $nomorHibah, $batch = null, $expDate = null, $sumber = null, $catatan = null)
    {
        $keterangan = 'Obat Hibah';
        if ($sumber) {
            $keterangan .= " dari {$sumber}";
        }
        if ($catatan) {
            $keterangan .= " {$catatan}";
        }
        if ($keterangan === 'Obat Hibah') {
            $keterangan .= ": {$nomorHibah}";
        }

        return $this->tambahStok(
            $obatId,
            $gudangId,
            $jumlah,
            $batch,
            $expDate,
            null,
            null,
            null,
            null,
            'obat_hibah',
            $hibahId,
            $keterangan
        );
    }

    /**
     * Return sold stok to the gudang and batch it was taken from, read from the invoice's kartu stok
     * (latest deductions first). Quantity without sale history goes to the newest usable batch of
     * the fallback gudang.
     *
     * @param string $refType kartu stok ref_type of the return (invoice_return, retur_pembelian)
     */
    public function kembalikanStokInvoice($obatId, $qty, $invoiceId, $fallbackGudangId, $refType, $refId, $keterangan)
    {
        return DB::transaction(function () use ($obatId, $qty, $invoiceId, $fallbackGudangId, $refType, $refId, $keterangan) {
            $remaining = round((float) $qty, 4);
            if ($remaining <= 0) {
                throw new \InvalidArgumentException('Jumlah stok yang dikembalikan harus lebih dari 0.');
            }

            $sold = KartuStok::where('obat_id', $obatId)
                ->where('ref_type', 'invoice_penjualan')
                ->where('ref_id', $invoiceId)
                ->where('tipe', 'keluar')
                ->select('gudang_id', 'batch', DB::raw('SUM(qty) as qty'), DB::raw('MAX(id) as last_id'))
                ->groupBy('gudang_id', 'batch')
                ->orderByDesc('last_id')
                ->get();

            // Earlier billing-edit returns and retur penjualan of this invoice: never give back more than was taken per batch
            $returned = KartuStok::where('obat_id', $obatId)
                ->where('tipe', 'masuk')
                ->where(function ($q) use ($invoiceId) {
                    $q->where(function ($w) use ($invoiceId) {
                        $w->where('ref_type', 'invoice_return')->where('ref_id', $invoiceId);
                    })->orWhere(function ($w) use ($invoiceId) {
                        $w->where('ref_type', 'retur_pembelian')
                            ->whereIn('ref_id', DB::table('finance_retur_pembelian')->where('invoice_id', $invoiceId)->select('id'));
                    });
                })
                ->select('gudang_id', 'batch', DB::raw('SUM(qty) as qty'))
                ->groupBy('gudang_id', 'batch')
                ->get()
                ->keyBy(function ($r) {
                    return $r->gudang_id . '|' . $r->batch;
                });

            foreach ($sold as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $alreadyReturned = (float) optional($returned->get($row->gudang_id . '|' . $row->batch))->qty;
                $part = min($remaining, round((float) $row->qty - $alreadyReturned, 4));
                if ($part <= 0) {
                    continue;
                }

                $expDate = ObatStokGudang::withTrashed()
                    ->where('obat_id', $obatId)
                    ->where('gudang_id', $row->gudang_id)
                    ->where('batch', $row->batch)
                    ->value('expiration_date');

                $this->tambahStok($obatId, $row->gudang_id, $part, $row->batch, $expDate, null, null, null, null, $refType, $refId, $keterangan);
                $remaining = round($remaining - $part, 4);
            }

            if ($remaining > 0) {
                if (!$fallbackGudangId) {
                    throw new \RuntimeException('Gudang pengembalian stok tidak diketahui.');
                }
                // No sale history (e.g. sold before kartu stok existed): newest usable batch of the fallback gudang
                $batchRow = ObatStokGudang::where('obat_id', $obatId)
                    ->where('gudang_id', $fallbackGudangId)
                    ->layakPakai()
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                $this->tambahStok(
                    $obatId,
                    $fallbackGudangId,
                    $remaining,
                    $batchRow ? $batchRow->batch : 'RETURN-' . date('YmdHis'),
                    $batchRow ? $batchRow->expiration_date : null,
                    null,
                    null,
                    null,
                    null,
                    $refType,
                    $refId,
                    $keterangan
                );
            }

            return true;
        });
    }
}
