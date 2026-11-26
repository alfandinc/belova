<?php

namespace App\Services\ERM;

use App\Models\ERM\Obat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hitung Harga Paket: the modal (cost) of one visit of a paket tindakan (erm_tindakan) and the price that
 * gives a target untung.
 *
 *   Modal per visit = Bahan (BHP of its tindakan × HPP) + Jasa Medis (tarif dokter + petugas of its tindakan)
 *                   + Alat (biaya per pakai × qty) + Biaya Lain (nominal per paket)
 *   Biaya alat per pakai = (harga alat × unit ÷ (umur × 12) + servis 12 bulan terakhir ÷ 12) ÷ (target pakai/bulan × unit)
 *   Harga saran = modal ÷ (1 − untung%), rounded UP to a multiple of PEMBULATAN.
 *
 * Untung is the margin on the selling price: (harga − modal) ÷ harga. A multi-visit paket (harga_paket_visit)
 * costs modal × number of visits.
 */
class HitungHargaService
{
    /** Paket whose untung is below this (%) get the red flag */
    public const UNTUNG_MINIMAL = 30;

    /** Default target untung (%) in the Hitung Harga form */
    public const UNTUNG_TARGET = 40;

    /** Harga saran is rounded up to a multiple of this */
    public const PEMBULATAN = 5000;

    public static function bulatkan(float $v): float
    {
        return $v > 0 ? ceil(round($v, 2) / self::PEMBULATAN) * self::PEMBULATAN : 0;
    }

    /** Price for a target untung: the exact price, the rounded one and what rounding added */
    public static function hargaSaran(float $modal, float $untungPersen): ?array
    {
        if ($modal <= 0 || $untungPersen < 0 || $untungPersen >= 100) {
            return null;
        }
        $mentah = $modal / (1 - $untungPersen / 100);
        $bulat = self::bulatkan($mentah);

        return [
            'mentah' => round($mentah, 2),
            'bulat' => $bulat,
            'selisih' => round($bulat - $mentah, 2),
            'untung_bulat' => self::untungPersen($bulat, $modal),
        ];
    }

    public static function untungPersen(?float $harga, float $modal): ?float
    {
        return $harga > 0 ? round(($harga - $modal) / $harga * 100, 1) : null;
    }

    public static function jumlahVisit(object|array $t): int
    {
        $t = (object) $t;

        return max(1, (int) ($t->multi_visit_total ?? 0) ?: 3);
    }

    public static function hargaEfektif(object|array $t): ?float
    {
        $t = (object) $t;
        if (!empty($t->diskon_active) && (float) ($t->harga_diskon ?? 0) > 0) {
            return (float) $t->harga_diskon;
        }

        return (float) ($t->harga ?? 0) > 0 ? (float) $t->harga : null;
    }

    // ================================================================= Alat

    /**
     * Cost data of alat tindakan, keyed by barang id. Without ids: every alat tindakan.
     * Harga alat = harga_perolehan on the barang, else the latest Pembelian; unit = stok (min 1).
     */
    public static function alatInfo(?array $barangIds = null): Collection
    {
        $q = DB::table('inv_barang as b')
            ->leftJoin('inv_stok_barang as s', 's.barang_id', '=', 'b.id')
            ->leftJoin('inv_ruangan as r', 'r.id', '=', 'b.ruangan_id')
            ->select('b.id', 'b.name', 'b.kode', 'b.merk', 'b.is_alat_tindakan', 'b.harga_perolehan', 'b.tanggal_perolehan',
                'b.umur_pakai_tahun', 'b.target_pakai_per_bulan', 's.jumlah as stok', 'r.name as ruangan');
        $barangIds === null ? $q->where('b.is_alat_tindakan', true) : $q->whereIn('b.id', $barangIds ?: [0]);
        $rows = $q->orderBy('b.name')->get();
        if ($rows->isEmpty()) {
            return collect();
        }
        $ids = $rows->pluck('id')->all();

        $pembelian = DB::table('inv_pembelian_barang')->whereIn('barang_id', $ids)->where('harga_satuan', '>', 0)
            ->orderByDesc('tanggal_pembelian')->orderByDesc('id')
            ->get(['barang_id', 'harga_satuan', 'tanggal_pembelian', 'no_faktur'])
            ->groupBy('barang_id')->map->first();

        $setahun = now()->subYear()->toDateString();
        $servis = DB::table('inv_maintenance_barang')->whereIn('barang_id', $ids)->where('tanggal_maintenance', '>=', $setahun)
            ->groupBy('barang_id')->selectRaw('barang_id, SUM(biaya_maintenance) as total, COUNT(*) as kali')->get()->keyBy('barang_id');

        $paket = DB::table('erm_tindakan_alat as ta')->join('erm_tindakan as t', 't.id', '=', 'ta.tindakan_id')
            ->whereIn('ta.barang_id', $ids)->orderBy('t.nama')
            ->get(['ta.barang_id', 't.id', 't.nama', 't.is_active', 'ta.qty'])->groupBy('barang_id');

        return $rows->mapWithKeys(function ($b) use ($pembelian, $servis, $paket, $setahun) {
            $beli = $pembelian->get($b->id);
            $harga = (float) $b->harga_perolehan > 0 ? (float) $b->harga_perolehan : ($beli ? (float) $beli->harga_satuan : null);
            $sumberHarga = (float) $b->harga_perolehan > 0 ? 'manual' : ($beli ? 'pembelian' : null);
            $tanggal = $b->tanggal_perolehan ?: ($beli->tanggal_pembelian ?? null);
            $unit = max(1, (int) $b->stok);
            $umur = (int) $b->umur_pakai_tahun;
            $target = (int) $b->target_pakai_per_bulan;
            $servis12 = (float) ($servis->get($b->id)->total ?? 0);

            $peringatan = [];
            if (!$harga) {
                $peringatan[] = 'Harga alat belum diisi';
            }
            if (!$umur) {
                $peringatan[] = 'Umur pakai belum diisi';
            }
            if (!$target) {
                $peringatan[] = 'Target pemakaian belum diisi';
            }

            $penyusutanBulan = $harga && $umur ? $harga * $unit / ($umur * 12) : 0;
            $servisBulan = $servis12 / 12;
            $kapasitas = $target * $unit;
            $perPakai = $kapasitas > 0 ? round(($penyusutanBulan + $servisBulan) / $kapasitas, 2) : null;

            // Average actual use per month over the last 12 months, or since the alat was acquired when that is later
            // (riwayat of its paket before the alat existed do not count)
            $bulan = 12;
            $mulai = $setahun;
            if ($tanggal && Carbon::parse($tanggal)->gt(now()->subYear())) {
                $mulai = Carbon::parse($tanggal)->toDateString();
                $bulan = max(1, round(Carbon::parse($tanggal)->floatDiffInMonths(now()), 2));
            }
            $jumlahPakai = ($paket->get($b->id) ?? collect())->isEmpty() ? 0 : (float) DB::table('erm_tindakan_alat as ta')
                ->join('erm_riwayat_tindakan as rt', 'rt.tindakan_id', '=', 'ta.tindakan_id')
                ->where('ta.barang_id', $b->id)
                ->where('rt.tanggal_tindakan', '>=', $mulai)
                ->sum('ta.qty');
            $aktual = round($jumlahPakai / $bulan, 1);

            return [$b->id => [
                'id' => (int) $b->id,
                'nama' => $b->name,
                'kode' => $b->kode,
                'merk' => $b->merk,
                'ruangan' => $b->ruangan,
                'is_alat_tindakan' => (bool) $b->is_alat_tindakan,
                'harga' => $harga,
                'harga_sumber' => $sumberHarga,
                'harga_perolehan' => $b->harga_perolehan !== null ? (float) $b->harga_perolehan : null,
                'pembelian_terakhir' => $beli ? ['harga' => (float) $beli->harga_satuan, 'tanggal' => $beli->tanggal_pembelian, 'no_faktur' => $beli->no_faktur] : null,
                'tanggal_perolehan' => $b->tanggal_perolehan,
                'tanggal' => $tanggal,
                'unit' => $unit,
                'umur_pakai_tahun' => $umur ?: null,
                'target_pakai_per_bulan' => $target ?: null,
                'servis_12_bulan' => $servis12,
                'servis_kali' => (int) ($servis->get($b->id)->kali ?? 0),
                'penyusutan_bulan' => round($penyusutanBulan, 2),
                'servis_bulan' => round($servisBulan, 2),
                'per_pakai' => $perPakai,
                'pakai_aktual_bulan' => $aktual,
                'terpakai_persen' => $kapasitas > 0 ? round($aktual / $kapasitas * 100, 1) : null,
                'paket' => ($paket->get($b->id) ?? collect())->map(fn ($p) => ['id' => (int) $p->id, 'nama' => $p->nama, 'is_active' => (bool) $p->is_active, 'qty' => (float) $p->qty])->values()->all(),
                'peringatan' => $peringatan,
            ]];
        });
    }

    // ================================================================= Paket

    /**
     * Modal of one visit of each paket, keyed by tindakan id.
     * $override[tindakan_id] = ['alat' => [[barang_id, qty]], 'biaya_lain' => [[biaya_lain_id, nominal]]]
     * replaces the saved alat / biaya lain (live preview before saving).
     */
    public static function hitungBanyak(Collection $tindakans, array $override = []): Collection
    {
        if ($tindakans->isEmpty()) {
            return collect();
        }
        $ids = $tindakans->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Tindakan (kode tindakan) of each paket, with their jasa medis
        $isi = DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_kode_tindakan as k', 'k.id', '=', 'tk.kode_tindakan_id')
            ->leftJoin('erm_jasa_medis as jm', 'jm.kode_tindakan_id', '=', 'k.id')
            ->whereIn('tk.tindakan_id', $ids)
            ->orderBy('k.nama')
            ->get(['tk.tindakan_id', 'k.id as kode_tindakan_id', 'k.nama', 'jm.id as jasa_id', 'jm.tarif_dokter', 'jm.tarif_employee']);
        $kodeIds = $isi->pluck('kode_tindakan_id')->unique()->values()->all();

        // BHP of each kode tindakan at HPP (HNA + PPN); never-bought obat use the Master Pembelian price
        $ppn = 1 + Obat::PPN_PERCENT / 100;
        $bhp = DB::table('erm_kode_tindakan_obat as ko')
            ->join('erm_obat as o', 'o.id', '=', 'ko.obat_id')
            ->leftJoinSub(MasterPembelianService::estimasiHppQuery(), 'est', 'est.obat_id', '=', 'o.id')
            ->whereIn('ko.kode_tindakan_id', $kodeIds ?: [0])
            ->orderBy('o.nama')
            ->select('ko.kode_tindakan_id', 'o.id as obat_id', 'o.nama', 'ko.qty', 'o.satuan')
            // Same rule as the Harga Paket Tindakan pengajuan list
            ->selectRaw("CASE WHEN COALESCE(o.hpp, 0) <= 0 AND est.hpp_estimasi > 0 THEN est.hpp_estimasi * {$ppn}
                ELSE COALESCE(NULLIF(o.hpp, 0), o.hna * {$ppn}, 0) END as hpp")
            ->selectRaw('(COALESCE(o.hpp, 0) <= 0 AND est.hpp_estimasi > 0) as estimasi')
            ->get()
            ->groupBy('kode_tindakan_id');

        $alatPivot = DB::table('erm_tindakan_alat')->whereIn('tindakan_id', $ids)->get(['tindakan_id', 'barang_id', 'qty'])->groupBy('tindakan_id');
        $lainPivot = DB::table('erm_tindakan_biaya_lain as tb')->join('erm_biaya_lain as bl', 'bl.id', '=', 'tb.biaya_lain_id')
            ->whereIn('tb.tindakan_id', $ids)->orderBy('bl.nama')
            ->get(['tb.tindakan_id', 'bl.id as biaya_lain_id', 'bl.nama', 'bl.is_active', 'tb.nominal', 'bl.nominal as nominal_master'])->groupBy('tindakan_id');

        // Alat / biaya lain per paket: saved, or the override
        $alatRows = [];
        $lainRows = [];
        foreach ($ids as $id) {
            $alatRows[$id] = isset($override[$id]['alat'])
                ? collect($override[$id]['alat'])->map(fn ($a) => (object) ['barang_id' => (int) $a['barang_id'], 'qty' => (float) $a['qty']])
                : ($alatPivot->get($id) ?? collect());
            $lainRows[$id] = isset($override[$id]['biaya_lain']) ? collect($override[$id]['biaya_lain']) : ($lainPivot->get($id) ?? collect());
        }
        $barangIds = collect($alatRows)->flatten(1)->pluck('barang_id')->unique()->values()->all();
        $alatInfo = $barangIds ? self::alatInfo($barangIds) : collect();

        $masterLain = null;
        if (collect($override)->contains(fn ($o) => isset($o['biaya_lain']))) {
            $masterLain = DB::table('erm_biaya_lain')->get(['id', 'nama', 'is_active', 'nominal'])->keyBy('id');
        }

        return $tindakans->mapWithKeys(function ($t) use ($isi, $bhp, $alatRows, $lainRows, $alatInfo, $masterLain) {
            $id = (int) $t->id;
            $peringatan = [];

            // Bahan + jasa per kode tindakan
            $kode = $isi->where('tindakan_id', $id)->values()->map(function ($k) use ($bhp, &$peringatan) {
                $obat = ($bhp->get($k->kode_tindakan_id) ?? collect())->map(fn ($o) => [
                    'obat_id' => (int) $o->obat_id,
                    'nama' => $o->nama,
                    'qty' => (float) $o->qty,
                    'satuan' => $o->satuan,
                    'hpp' => round((float) $o->hpp, 2),
                    'subtotal' => round((float) $o->qty * (float) $o->hpp, 2),
                    'estimasi' => (bool) $o->estimasi,
                ])->values();
                $tanpaHpp = $obat->where('hpp', '<=', 0)->count();
                if ($tanpaHpp) {
                    $peringatan[] = $k->nama . ': ' . $tanpaHpp . ' BHP belum ada HPP';
                }
                if (!$k->jasa_id) {
                    $peringatan[] = $k->nama . ': tarif jasa medis belum diatur';
                }

                return [
                    'id' => (int) $k->kode_tindakan_id,
                    'nama' => $k->nama,
                    'bahan' => round($obat->sum('subtotal'), 2),
                    'obat' => $obat->all(),
                    'jasa_dokter' => (float) $k->tarif_dokter,
                    'jasa_petugas' => (float) $k->tarif_employee,
                    'jasa' => (float) $k->tarif_dokter + (float) $k->tarif_employee,
                    'jasa_diatur' => (bool) $k->jasa_id,
                ];
            });
            if ($kode->isEmpty()) {
                $peringatan[] = 'Paket belum berisi tindakan';
            }

            $alat = $alatRows[$id]->map(function ($a) use ($alatInfo, &$peringatan) {
                $info = $alatInfo->get((int) $a->barang_id);
                if (!$info) {
                    return null;
                }
                if ($info['peringatan']) {
                    $peringatan[] = $info['nama'] . ': ' . implode(', ', $info['peringatan']);
                }
                if (!$info['is_alat_tindakan']) {
                    $peringatan[] = $info['nama'] . ': bukan lagi alat tindakan';
                }

                return [
                    'barang_id' => $info['id'],
                    'nama' => $info['nama'],
                    'qty' => (float) $a->qty,
                    'per_pakai' => $info['per_pakai'],
                    'subtotal' => round((float) $info['per_pakai'] * (float) $a->qty, 2),
                    'peringatan' => $info['peringatan'],
                ];
            })->filter()->values();

            $lain = $lainRows[$id]->map(function ($l) use ($masterLain) {
                $l = (object) $l;
                $m = $masterLain?->get((int) $l->biaya_lain_id);

                return [
                    'biaya_lain_id' => (int) $l->biaya_lain_id,
                    'nama' => $l->nama ?? $m->nama ?? '-',
                    'nominal' => round((float) $l->nominal, 2),
                    'nominal_master' => (float) ($l->nominal_master ?? $m->nominal ?? 0),
                    'is_active' => (bool) ($l->is_active ?? $m->is_active ?? true),
                ];
            })->values();

            $bahan = round($kode->sum('bahan'), 2);
            $jasa = round($kode->sum('jasa'), 2);
            $biayaAlat = round($alat->sum('subtotal'), 2);
            $biayaLain = round($lain->sum('nominal'), 2);
            $modal = round($bahan + $jasa + $biayaAlat + $biayaLain, 2);

            $harga = (float) $t->harga > 0 ? (float) $t->harga : null;
            $efektif = self::hargaEfektif($t);
            $visit = self::jumlahVisit($t);
            $hargaVisit = (float) ($t->harga_paket_visit ?? 0) > 0 ? (float) $t->harga_paket_visit : null;

            return [$id => [
                'id' => $id,
                'nama' => $t->nama,
                'tindakan' => $kode->all(),
                'alat' => $alat->all(),
                'biaya_lain' => $lain->all(),
                'bahan' => $bahan,
                'jasa' => $jasa,
                'biaya_alat' => $biayaAlat,
                'biaya_lain_total' => $biayaLain,
                'modal' => $modal,
                'harga' => $harga,
                'harga_diskon' => (float) $t->harga_diskon > 0 ? (float) $t->harga_diskon : null,
                'diskon_active' => (bool) $t->diskon_active,
                'harga_efektif' => $efektif,
                'untung' => $efektif ? round($efektif - $modal, 2) : null,
                'untung_persen' => self::untungPersen($efektif, $modal),
                'untung_persen_normal' => self::untungPersen($harga, $modal),
                'jasa_persen' => $efektif && $jasa > 0 ? round($jasa / $efektif * 100, 1) : null,
                'harga_paket_visit' => $hargaVisit,
                // Sold as a multi-visit package: has a multi-visit price or more than one visit
                'multi_visit' => $hargaVisit !== null || (int) ($t->multi_visit_total ?? 0) > 1,
                'jumlah_visit' => $visit,
                'modal_paket_visit' => round($modal * $visit, 2),
                'untung_persen_paket_visit' => self::untungPersen($hargaVisit, $modal * $visit),
                'peringatan' => array_values(array_unique($peringatan)),
            ]];
        });
    }

    public static function hitung(object $tindakan, array $override = []): array
    {
        return self::hitungBanyak(collect([$tindakan]), $override ? [(int) $tindakan->id => $override] : [])->first();
    }

    /** Status label for the list: rugi / tipis / aman / belum ada harga */
    public static function status(array $h): string
    {
        if (!$h['harga_efektif']) {
            return 'tanpa_harga';
        }
        if ($h['untung_persen'] < 0) {
            return 'rugi';
        }

        return $h['untung_persen'] < self::UNTUNG_MINIMAL ? 'tipis' : 'aman';
    }

    // ================================================================= Balik modal

    /**
     * Payback of each alat tindakan. Uang kembali = per riwayat tindakan since the alat was acquired:
     * untung kotor of the paket (harga sekarang − bahan − jasa − biaya lain), shared over the alat of the
     * paket by their biaya per pakai; minus servis since acquisition. An estimate: uses today's prices.
     */
    public static function balikModal(Collection $alatInfo): Collection
    {
        if ($alatInfo->isEmpty()) {
            return collect();
        }
        $pivot = DB::table('erm_tindakan_alat')->whereIn('barang_id', $alatInfo->keys()->all())->get(['tindakan_id', 'barang_id', 'qty']);
        $tindakanIds = $pivot->pluck('tindakan_id')->unique()->values()->all();
        $tindakans = DB::table('erm_tindakan')->whereIn('id', $tindakanIds ?: [0])
            ->get(['id', 'nama', 'harga', 'harga_diskon', 'diskon_active', 'harga_paket_visit', 'multi_visit_total']);
        $hitung = self::hitungBanyak($tindakans);

        return $alatInfo->map(function ($a) use ($pivot, $hitung) {
            $modal = $a['harga'] ? round($a['harga'] * $a['unit'], 2) : null;
            $sejak = $a['tanggal'];
            $paketIds = $pivot->where('barang_id', $a['id'])->pluck('tindakan_id')->all();
            if (!$sejak && $paketIds) {
                $sejak = DB::table('erm_riwayat_tindakan')->whereIn('tindakan_id', $paketIds)->min('tanggal_tindakan');
            }

            $kembali = 0.0;
            $jumlah = 0;
            foreach ($paketIds as $pid) {
                $h = $hitung->get($pid);
                if (!$h || !$h['harga_efektif']) {
                    continue;
                }
                $kotor = $h['harga_efektif'] - $h['bahan'] - $h['jasa'] - $h['biaya_lain_total'];
                $alatPaket = collect($h['alat']);
                $totalAlat = $alatPaket->sum('subtotal');
                $porsi = $totalAlat > 0
                    ? (float) ($alatPaket->firstWhere('barang_id', $a['id'])['subtotal'] ?? 0) / $totalAlat
                    : 1 / max(1, $alatPaket->count());
                $kali = $sejak ? DB::table('erm_riwayat_tindakan')->where('tindakan_id', $pid)->where('tanggal_tindakan', '>=', $sejak)->count() : 0;
                $jumlah += $kali;
                $kembali += $kali * $kotor * $porsi;
            }
            $servisSejak = $sejak ? (float) DB::table('inv_maintenance_barang')->where('barang_id', $a['id'])->where('tanggal_maintenance', '>=', $sejak)->sum('biaya_maintenance') : 0;
            $kembali = round($kembali - $servisSejak, 2);

            $bulanBerjalan = $sejak ? max(1, round(Carbon::parse($sejak)->floatDiffInMonths(now()), 2)) : null;
            $perBulan = $bulanBerjalan ? round($kembali / $bulanBerjalan, 2) : null;
            $sisa = $modal !== null ? round(max(0, $modal - $kembali), 2) : null;

            return [
                'modal' => $modal,
                'sejak' => $sejak,
                'jumlah_tindakan' => $jumlah,
                'servis_sejak' => $servisSejak,
                'kembali' => $kembali,
                'kembali_persen' => $modal ? round(min(100, max(0, $kembali / $modal * 100)), 1) : null,
                'sisa' => $sisa,
                'per_bulan' => $perBulan,
                'lunas' => $modal !== null && $kembali >= $modal,
                'bulan_lagi' => $sisa > 0 && $perBulan > 0 ? (int) ceil($sisa / $perBulan) : null,
            ];
        });
    }
}
