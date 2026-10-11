<?php

namespace App\Services\ERM;

use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\FakturRetur;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\KartuStok;
use App\Models\ERM\ObatExpiredTindakLanjut;
use App\Models\ERM\ObatStokGudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Gudang expired (karantina): the only way expired stok leaves the active gudang,
 * and the only ways it leaves the gudang expired (Retur Pembelian or Pemusnahan).
 */
class GudangExpiredService
{
    public const METODE_PEMUSNAHAN = [
        'Diserahkan ke pihak ketiga pengolah limbah B3',
        'Dibakar (insinerasi)',
        'Dihancurkan / dilarutkan lalu dibuang',
        'Lainnya',
    ];

    public function mapping(): ?GudangMapping
    {
        return GudangMapping::getActiveMapping('expired');
    }

    public function gudangId(): ?int
    {
        $mapping = $this->mapping();
        return $mapping ? (int) $mapping->gudang_id : null;
    }

    public function isGudangExpired($gudangId): bool
    {
        $expiredId = $this->gudangId();
        return $expiredId !== null && (int) $gudangId === $expiredId;
    }

    /**
     * Stok in the gudang expired may only leave through Retur Pembelian or Pemusnahan.
     */
    public function assertBukanGudangExpired($gudangId, string $aksi): void
    {
        if ($this->isGudangExpired($gudangId)) {
            $nama = optional(Gudang::find($gudangId))->nama ?? 'gudang expired';
            throw new RuntimeException(
                "Stok di {$nama} (gudang expired) tidak bisa {$aksi}. "
                . 'Keluarkan lewat Tindak Lanjut di Manajemen Stok: Retur ke Supplier atau Pemusnahan.'
            );
        }
    }

    /**
     * Move the whole quantity of one batch to the gudang expired, with kartu stok on both sides.
     * Must run inside a transaction with $source locked for update.
     */
    public function pindahkanBatch(ObatStokGudang $source, string $keterangan = null, ?int $userId = null): float
    {
        $mapping = $this->mapping();
        if (!$mapping || !$mapping->gudang_id) {
            throw new RuntimeException('Mapping gudang expired belum diset.');
        }
        $targetGudangId = (int) $mapping->gudang_id;

        $qty = (float) $source->stok;
        if ($qty <= 0) {
            throw new RuntimeException('Batch ini tidak memiliki stok untuk dipindahkan.');
        }
        if ((int) $source->gudang_id === $targetGudangId) {
            throw new RuntimeException('Batch ini sudah berada di gudang expired.');
        }

        $userId = $userId ?? Auth::id();
        $targetGudang = $mapping->gudang ?: Gudang::find($targetGudangId);
        $sourceGudangName = optional($source->gudang)->nama ?? ('Gudang #' . $source->gudang_id);
        $targetGudangName = $targetGudang ? $targetGudang->nama : ('Gudang #' . $targetGudangId);

        // Match on the unique key (obat_id, gudang_id, batch) only, so an existing row with a
        // different ED is reused instead of hitting the unique key on insert
        $target = ObatStokGudang::withTrashed()
            ->where('obat_id', $source->obat_id)
            ->where('gudang_id', $targetGudangId)
            ->where('batch', $source->batch)
            ->lockForUpdate()
            ->first();

        if ($target && $target->trashed()) {
            // A deleted row counts as empty; never revive its old quantity
            $target->restore();
            $target->stok = 0;
            $target->expiration_date = $source->expiration_date;
        }

        if (!$target) {
            $target = ObatStokGudang::create([
                'obat_id' => $source->obat_id,
                'gudang_id' => $targetGudangId,
                'stok' => 0,
                'batch' => $source->batch,
                'expiration_date' => $source->expiration_date,
                'rak' => $source->rak,
                'lokasi' => $source->lokasi,
            ]);
        }

        $target->stok = (float) $target->stok + $qty;
        if (!$target->expiration_date && $source->expiration_date) {
            $target->expiration_date = $source->expiration_date;
        }
        $target->save();

        $source->stok = 0;
        $source->save();

        $keterangan = $keterangan ? $keterangan . ' - ' : '';

        KartuStok::create([
            'obat_id' => $source->obat_id,
            'gudang_id' => $source->gudang_id,
            'tanggal' => now(),
            'tipe' => 'keluar',
            'qty' => $qty,
            'stok_setelah' => 0,
            'batch' => $source->batch,
            'keterangan' => Str::limit($keterangan . 'Pindah ke gudang expired ' . $targetGudangName, 188),
            'ref_type' => 'mutasi_gudang_expired',
            'ref_id' => $source->id,
            'user_id' => $userId,
        ]);

        KartuStok::create([
            'obat_id' => $target->obat_id,
            'gudang_id' => $targetGudangId,
            'tanggal' => now(),
            'tipe' => 'masuk',
            'qty' => $qty,
            'stok_setelah' => (float) $target->stok,
            'batch' => $target->batch,
            'keterangan' => Str::limit($keterangan . 'Mutasi expired dari ' . $sourceGudangName, 188),
            'ref_type' => 'mutasi_gudang_expired',
            'ref_id' => $source->id,
            'user_id' => $userId,
        ]);

        return $qty;
    }

    /**
     * Karantina (manual, from the Akan Expired modal): move every batch past its real ED out of the
     * active gudang into the gudang expired. Migrated batches (ED not known yet) are never moved.
     * Each batch is its own transaction so one failure does not block the rest.
     */
    public function pindahkanSemuaYangExpired(?int $userId = null, string $keterangan = 'Karantina manual'): array
    {
        $targetGudangId = $this->gudangId();
        if (!$targetGudangId) {
            throw new RuntimeException('Mapping gudang expired belum diset.');
        }

        $ids = ObatStokGudang::where('stok', '>', 0)
            ->edTerverifikasi()
            ->whereDate('expiration_date', '<', now()->toDateString())
            ->where('gudang_id', '!=', $targetGudangId)
            ->pluck('id');

        $result = ['batch' => 0, 'qty' => 0.0, 'gagal' => []];
        foreach ($ids as $id) {
            try {
                $qty = DB::transaction(function () use ($id, $userId, $keterangan) {
                    $source = ObatStokGudang::with('gudang')->lockForUpdate()->find($id);
                    if (!$source || (float) $source->stok <= 0 || !$source->isExpired()) {
                        return 0;
                    }
                    return $this->pindahkanBatch($source, $keterangan, $userId);
                });
                if ($qty > 0) {
                    $result['batch']++;
                    $result['qty'] += $qty;
                }
            } catch (\Throwable $e) {
                $result['gagal'][] = ['id' => $id, 'message' => $e->getMessage()];
            }
        }

        return $result;
    }

    /**
     * Pemusnahan: write off every batch of an obat in the gudang expired and record a Berita Acara.
     */
    public function musnahkan(int $obatId, array $data, ?int $userId = null): array
    {
        $gudangId = $this->gudangId();
        if (!$gudangId) {
            throw new RuntimeException('Mapping gudang expired belum diset.');
        }
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($obatId, $gudangId, $data, $userId) {
            $rows = $this->stokSiapTindakLanjut($obatId, $gudangId);
            $nomor = $this->nomorDokumen('BAP');
            $qtyTotal = 0;

            foreach ($rows as $row) {
                $qty = (float) $row->stok;

                ObatExpiredTindakLanjut::create([
                    'obat_id' => $row->obat_id,
                    'obat_stok_gudang_id' => $row->id,
                    'jumlah' => $qty,
                    'expiration_date' => $row->expiration_date,
                    'tindak_lanjut' => 'dimusnahkan',
                    'status' => ObatExpiredTindakLanjut::STATUS_SELESAI,
                    'nomor_dokumen' => $nomor,
                    'tanggal' => $data['tanggal'],
                    'metode' => $data['metode'],
                    'saksi_1' => $data['saksi_1'],
                    'saksi_2' => $data['saksi_2'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                $row->stok = 0;
                $row->save();

                KartuStok::create([
                    'obat_id' => $row->obat_id,
                    'gudang_id' => $row->gudang_id,
                    'tanggal' => now(),
                    'tipe' => 'keluar',
                    'qty' => $qty,
                    'stok_setelah' => 0,
                    'batch' => $row->batch,
                    'keterangan' => Str::limit('Pemusnahan obat expired, Berita Acara ' . $nomor, 188),
                    'ref_type' => 'expired_tindak_lanjut',
                    'ref_id' => $row->id,
                    'user_id' => $userId,
                ]);

                $qtyTotal += $qty;
            }

            return ['nomor_dokumen' => $nomor, 'batch' => $rows->count(), 'qty' => $qtyTotal];
        });
    }

    /**
     * Retur: create Retur Pembelian drafts (one per original faktur) from the gudang expired.
     * Stok leaves the gudang expired only when the retur is approved in Retur Pembelian.
     */
    public function ajukanRetur(int $obatId, array $data, ?int $userId = null): array
    {
        $gudangId = $this->gudangId();
        if (!$gudangId) {
            throw new RuntimeException('Mapping gudang expired belum diset.');
        }
        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use ($obatId, $gudangId, $data, $userId) {
            $rows = $this->stokSiapTindakLanjut($obatId, $gudangId);

            // Each batch must trace back to the faktur pembelian it was bought on
            $perFaktur = [];
            $tidakDitemukan = [];
            foreach ($rows as $row) {
                $qty = (float) $row->stok;
                if (abs($qty - round($qty)) > 0.0001) {
                    throw new RuntimeException("Stok batch {$row->batch} ({$qty}) bukan bilangan bulat; retur pembelian dihitung per unit utuh. Koreksi stok atau pilih Pemusnahan.");
                }

                $fakturItem = FakturBeliItem::where('obat_id', $row->obat_id)
                    ->where('batch', $row->batch)
                    ->whereHas('fakturbeli', function ($q) {
                        $q->whereNotNull('pemasok_id');
                    })
                    ->orderByDesc('id')
                    ->first();

                if (!$fakturItem) {
                    $tidakDitemukan[] = $row->batch ?: '(tanpa batch)';
                    continue;
                }
                $perFaktur[$fakturItem->fakturbeli_id][] = [$row, $fakturItem];
            }

            if ($tidakDitemukan) {
                throw new RuntimeException(
                    'Faktur pembelian untuk batch ' . implode(', ', $tidakDitemukan) . ' tidak ditemukan, jadi tidak bisa diretur ke supplier. '
                    . 'Pilih Pemusnahan untuk batch tersebut.'
                );
            }

            $returs = [];
            foreach ($perFaktur as $fakturbeliId => $items) {
                $fakturItem = $items[0][1];
                $retur = FakturRetur::create([
                    'fakturbeli_id' => $fakturbeliId,
                    'pemasok_id' => optional($fakturItem->fakturbeli)->pemasok_id,
                    'no_retur' => 'RET-ED-' . now()->format('YmdHis') . '-' . $fakturbeliId,
                    'tanggal_retur' => $data['tanggal'],
                    'notes' => Str::limit('Retur obat expired. ' . ($data['notes'] ?? ''), 1000),
                    'status' => 'diajukan',
                ]);

                foreach ($items as [$row, $item]) {
                    $retur->items()->create([
                        'fakturbeli_item_id' => $item->id,
                        'obat_id' => $row->obat_id,
                        'gudang_id' => $row->gudang_id,
                        'qty' => (int) round((float) $row->stok),
                        'batch' => $row->batch,
                        'expiration_date' => $row->expiration_date,
                        'alasan' => 'Expired',
                        'status' => 'diajukan',
                    ]);

                    ObatExpiredTindakLanjut::create([
                        'obat_id' => $row->obat_id,
                        'obat_stok_gudang_id' => $row->id,
                        'jumlah' => (float) $row->stok,
                        'expiration_date' => $row->expiration_date,
                        'tindak_lanjut' => 'diretur',
                        'status' => ObatExpiredTindakLanjut::STATUS_MENUNGGU_RETUR,
                        'nomor_dokumen' => $retur->no_retur,
                        'tanggal' => $data['tanggal'],
                        'fakturretur_id' => $retur->id,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $userId,
                    ]);
                }
                $returs[] = $retur;
            }

            return ['returs' => $returs, 'batch' => $rows->count()];
        });
    }

    /**
     * Called from Retur Pembelian approve / reject so the follow-up status matches the retur.
     */
    public function sinkronStatusRetur(FakturRetur $retur, string $statusRetur): void
    {
        $status = $statusRetur === 'diapprove'
            ? ObatExpiredTindakLanjut::STATUS_SELESAI
            : ObatExpiredTindakLanjut::STATUS_BATAL;

        ObatExpiredTindakLanjut::where('fakturretur_id', $retur->id)
            ->where('status', ObatExpiredTindakLanjut::STATUS_MENUNGGU_RETUR)
            ->update(['status' => $status]);
    }

    /**
     * Batches of the obat with stok in the gudang expired that are not already waiting on a retur.
     */
    private function stokSiapTindakLanjut(int $obatId, int $gudangId)
    {
        $rows = ObatStokGudang::where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->where('stok', '>', 0)
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            throw new RuntimeException('Tidak ada stok di gudang expired untuk ditindaklanjuti.');
        }

        $menungguRetur = ObatExpiredTindakLanjut::whereIn('obat_stok_gudang_id', $rows->pluck('id'))
            ->where('status', ObatExpiredTindakLanjut::STATUS_MENUNGGU_RETUR)
            ->with('fakturRetur:id,no_retur')
            ->get();
        if ($menungguRetur->isNotEmpty()) {
            $nomor = $menungguRetur->pluck('fakturRetur.no_retur')->filter()->unique()->implode(', ');
            throw new RuntimeException("Obat ini masih menunggu persetujuan Retur Pembelian {$nomor}. Approve atau tolak retur tersebut terlebih dahulu.");
        }

        return $rows;
    }

    /**
     * Sequential document number per day, e.g. BAP-20261011-001.
     */
    public function nomorDokumen(string $prefix): string
    {
        $base = $prefix . '-' . now()->format('Ymd') . '-';
        $last = ObatExpiredTindakLanjut::where('nomor_dokumen', 'like', $base . '%')
            ->lockForUpdate()
            ->orderByDesc('nomor_dokumen')
            ->value('nomor_dokumen');
        $next = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return $base . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
