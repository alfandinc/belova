<?php

namespace App\Services\ERM;

use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\ResepDokter;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stok around the resep gudang (the gudang mapped to "Resep Farmasi", where payment takes resep stok from):
 *  - stok of an obat in the OTHER gudangs, so E-Resep can tell "kosong di apotek" from "kosong di semua gudang";
 *  - saran isi ulang: which obat to transfer into the resep gudang for the patients registered for the coming days.
 * "Gudang lain" never includes the gudang ED, and only counts batches that are still usable (not past ED).
 */
class StokResepService
{
    // Saran isi ulang: registered visits from today up to this many days ahead (1 = today only)
    public const HARI_KUNJUNGAN = [1, 3, 7, 14];

    // A patient's expected resep = average of their last N visits with a resep at the same klinik, no older than a year
    public const RIWAYAT_KUNJUNGAN = 3;
    public const RIWAYAT_MAKS_HARI = 365;

    // Resolved once per request: both are asked several times while one page is built
    private static $gudangResepId = false;
    private static $gudangEdId = false;

    public function gudangResepId(): ?int
    {
        if (self::$gudangResepId === false) {
            $id = GudangMapping::getDefaultGudangId('resep');
            self::$gudangResepId = $id ? (int) $id : null;
        }

        return self::$gudangResepId;
    }

    private function gudangEdId(): ?int
    {
        if (self::$gudangEdId === false) {
            self::$gudangEdId = app(GudangExpiredService::class)->gudangId();
        }

        return self::$gudangEdId;
    }

    /** Gudangs a transfer into the resep gudang can come from: all but the resep gudang and the gudang ED. */
    public function gudangLain()
    {
        $resepId = $this->gudangResepId();
        $edId = $this->gudangEdId();

        return Gudang::orderBy('nama')->get(['id', 'nama'])
            ->reject(fn ($g) => (int) $g->id === (int) $resepId || ($edId && (int) $g->id === (int) $edId))
            ->values();
    }

    /**
     * Stok outside the resep gudang, per obat, usable and expired apart (expired batches are shown with a warning,
     * never counted as available):
     * [obat_id => ['total' => usable, 'ed' => expired, 'gudang' => [['id', 'nama', 'stok' => usable, 'stok_ed' => expired], ...]]]
     * (most usable first). Obat without stok elsewhere are left out. Empty when no resep gudang is mapped.
     */
    public function stokGudangLain(array $obatIds): array
    {
        $resepId = $this->gudangResepId();
        $obatIds = array_values(array_unique(array_filter(array_map('intval', $obatIds))));
        if (!$resepId || !$obatIds) {
            return [];
        }

        $edId = $this->gudangEdId();
        $rows = ObatStokGudang::query()
            ->whereIn('obat_id', $obatIds)
            ->where('gudang_id', '!=', $resepId)
            ->when($edId, fn ($q) => $q->where('gudang_id', '!=', $edId))
            ->where('stok', '>', 0)
            ->groupBy('obat_id', 'gudang_id')
            ->selectRaw('obat_id, gudang_id, SUM(stok) as stok, SUM(stok * ' . ObatStokGudang::sqlSudahEd() . ') as stok_ed')
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $nama = Gudang::whereIn('id', $rows->pluck('gudang_id')->unique()->all())->pluck('nama', 'id');
        $hasil = [];
        foreach ($rows->sortByDesc(fn ($r) => (float) $r->stok - (float) $r->stok_ed) as $row) {
            $obatId = (int) $row->obat_id;
            $ed = round((float) $row->stok_ed, 2);
            $layak = round((float) $row->stok - $ed, 2);
            $hasil[$obatId] ??= ['total' => 0.0, 'ed' => 0.0, 'gudang' => []];
            $hasil[$obatId]['total'] = round($hasil[$obatId]['total'] + $layak, 2);
            $hasil[$obatId]['ed'] = round($hasil[$obatId]['ed'] + $ed, 2);
            $hasil[$obatId]['gudang'][] = [
                'id' => (int) $row->gudang_id,
                'nama' => $nama[$row->gudang_id] ?? 'Gudang #' . $row->gudang_id,
                'stok' => $layak,
                'stok_ed' => $ed,
            ];
        }

        return $hasil;
    }

    /** Racikan in these wadah are measured, not counted: the stok taken keeps its fraction. */
    public static function racikanPecahan(?string $wadahNama): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $wadahNama ?? '')));

        return in_array($normalized, ['botol', 'cream / lotion'], true);
    }

    /**
     * Stok a racikan row takes out = dosis racik × bungkus ÷ dosis obat (rounded up unless the wadah is measured).
     * Works on ResepFarmasi and ResepDokter rows (same columns); needs the obat and wadah relations.
     */
    public static function stokDikurangiRacikan($resep): float
    {
        $prescribedDosis = 0.0;
        $baseDosis = 0.0;

        if (preg_match('/(\d+(?:[.,]\d+)?)/', $resep->dosis ?? '', $m)) {
            $prescribedDosis = (float) str_replace(',', '.', $m[1]);
        }
        if ($resep->obat && !empty($resep->obat->dosis) && preg_match('/(\d+(?:[.,]\d+)?)/', $resep->obat->dosis, $m2)) {
            $baseDosis = (float) str_replace(',', '.', $m2[1]);
        }

        if ($baseDosis <= 0 || $prescribedDosis <= 0) {
            return 0.0;
        }

        $rawReduction = ($prescribedDosis * (float) ($resep->bungkus ?? 1)) / $baseDosis;
        if (self::racikanPecahan(optional($resep->wadah)->nama)) {
            return round($rawReduction, 3);
        }

        return (float) ceil($rawReduction);
    }

    /** Stok one resep row needs: non racikan = jumlah, racikan = stokDikurangiRacikan(). */
    public static function kebutuhanResep($resep): float
    {
        return $resep->racikan_ke ? self::stokDikurangiRacikan($resep) : (float) ($resep->jumlah ?? 0);
    }

    /** Of the given gudangs, the one holding the most different obat (the gudang utama in practice). */
    private function gudangTerbanyakObat(array $gudangIds): ?int
    {
        if (!$gudangIds) {
            return null;
        }
        $id = ObatStokGudang::query()
            ->layakPakai()
            ->whereIn('gudang_id', $gudangIds)
            ->where('stok', '>', 0)
            ->groupBy('gudang_id')
            ->orderByRaw('COUNT(DISTINCT obat_id) DESC')
            ->value('gudang_id');

        return (int) ($id ?: $gudangIds[0]);
    }

    public function normalizeSaranParams(array $input): array
    {
        $hari = (int) ($input['hari_kunjungan'] ?? 0);

        return [
            'hari_kunjungan' => in_array($hari, self::HARI_KUNJUNGAN, true) ? $hari : 7,
            'gudang_asal_id' => (int) ($input['gudang_asal_id'] ?? 0) ?: null,
        ];
    }

    /**
     * Saran isi ulang gudang resep, from the patients registered for today .. today + hari_kunjungan − 1.
     * No stok minimum is typed in per obat. Per registered visit that is not cancelled and not paid yet:
     *   - it already has a resep (farmasi's, else the dokter's): that resep is the need ("pasti");
     *   - no resep yet and not examined: the need is predicted from the patient's own history = the average, per
     *     obat, of their last RIWAYAT_KUNJUNGAN visits with a resep at the same klinik ("prediksi");
     *   - a patient without such history is not predicted (counted in ringkasan.tanpa_riwayat).
     * An obat is listed when need > stok layak pakai di gudang resep + transfers still waiting for approval;
     * saran transfer = the shortage, capped at the usable stok in the gudang asal.
     *
     * Returns ['gudang_resep', 'gudang_asal', 'gudang_pilihan', 'params', 'periode', 'rows', 'ringkasan'].
     */
    public function saranIsiUlang(array $params): array
    {
        $resepId = $this->gudangResepId();
        $gudangLain = $this->gudangLain();
        $mulai = Carbon::today();
        $sampai = Carbon::today()->addDays($params['hari_kunjungan'] - 1);
        $hasil = [
            'gudang_resep' => $resepId ? ['id' => $resepId, 'nama' => Gudang::where('id', $resepId)->value('nama')] : null,
            'gudang_asal' => null,
            'gudang_pilihan' => $gudangLain->map(fn ($g) => ['id' => (int) $g->id, 'nama' => $g->nama])->all(),
            'params' => $params,
            'periode' => ['mulai' => $mulai->format('d/m/Y'), 'sampai' => $sampai->format('d/m/Y')],
            'rows' => [],
            'ringkasan' => ['kunjungan' => 0, 'dengan_resep' => 0, 'diprediksi' => 0, 'tanpa_riwayat' => 0, 'obat_kurang' => 0],
        ];
        if (!$resepId) {
            return $hasil;
        }

        [$butuh, $hasil['ringkasan']] = $this->kebutuhanKunjungan($mulai, $sampai);

        // Gudang asal: the user's choice, else the gudang holding the most different obat
        $asal = $gudangLain->firstWhere('id', $params['gudang_asal_id'])
            ?: $gudangLain->firstWhere('id', $this->gudangTerbanyakObat($gudangLain->pluck('id')->all()));
        $asalId = $asal ? (int) $asal->id : null;
        $hasil['gudang_asal'] = $asal ? ['id' => $asalId, 'nama' => $asal->nama] : null;
        $hasil['params']['gudang_asal_id'] = $asalId;
        if (!$butuh) {
            return $hasil;
        }

        $obatIds = array_keys($butuh);
        $stokResep = ObatStokGudang::query()
            ->layakPakai()
            ->where('gudang_id', $resepId)
            ->whereIn('obat_id', $obatIds)
            ->groupBy('obat_id')
            ->selectRaw('obat_id, SUM(stok) as stok')
            ->pluck('stok', 'obat_id');

        // Transfers into the resep gudang still waiting for approval: counted, so the same obat is not requested twice
        $dalamTransfer = DB::table('erm_mutasi_gudang_items as i')
            ->join('erm_mutasi_gudang as m', 'm.id', '=', 'i.mutasi_id')
            ->where('m.status', 'pending')
            ->where('m.gudang_tujuan_id', $resepId)
            ->whereIn('i.obat_id', $obatIds)
            ->groupBy('i.obat_id')
            ->selectRaw('i.obat_id, SUM(i.jumlah) as jumlah')
            ->pluck('jumlah', 'obat_id');

        // Only the obat that are short need their names and their stok elsewhere
        $kurang = [];
        foreach ($butuh as $obatId => $b) {
            $kebutuhan = $b['pasti'] + $b['prediksi'];
            $stok = round((float) ($stokResep[$obatId] ?? 0), 2);
            $transfer = round((float) ($dalamTransfer[$obatId] ?? 0), 2);
            if ($kebutuhan - $stok - $transfer > 0.0001) {
                $kurang[$obatId] = $b + ['kebutuhan' => $kebutuhan, 'stok' => $stok, 'transfer' => $transfer];
            }
        }
        if (!$kurang) {
            return $hasil;
        }

        $obats = Obat::whereIn('id', array_keys($kurang))->get(['id', 'nama', 'kode_obat', 'satuan', 'satuan_stok'])->keyBy('id');
        $stokLain = $this->stokGudangLain(array_keys($kurang));

        foreach ($kurang as $obatId => $k) {
            $obat = $obats->get($obatId);
            if (!$obat) {
                continue; // nonaktif / deleted: not refilled
            }
            $lain = $stokLain[$obatId] ?? ['total' => 0.0, 'gudang' => []];
            // Only usable stok can be transferred for a resep
            $lain['gudang'] = array_values(array_filter($lain['gudang'], fn ($g) => $g['stok'] > 0));
            $stokAsal = (float) (collect($lain['gudang'])->firstWhere('id', $asalId)['stok'] ?? 0);
            $selisih = $k['kebutuhan'] - $k['stok'] - $k['transfer'];
            $saran = min(ceil($selisih - 0.0001), floor($stokAsal));

            if ($saran > 0 && $saran >= $selisih - 0.0001) {
                $status = 'transfer';
            } elseif ($saran > 0) {
                $status = 'sebagian';
            } elseif ($lain['total'] > 0) {
                $status = 'gudang_lain'; // not in the chosen gudang asal, but another gudang has it
            } else {
                $status = 'pengadaan';
            }

            $hasil['rows'][] = [
                'obat_id' => (int) $obatId,
                'nama' => $obat->nama,
                'kode' => $obat->kode_obat,
                'satuan' => $obat->satuan_stok ?: $obat->satuan,
                'pasti' => round($k['pasti'], 2),
                'prediksi' => round($k['prediksi'], 2),
                'kebutuhan' => round($k['kebutuhan'], 2),
                'kunjungan' => count($k['kunjungan']),
                'stok_resep' => $k['stok'],
                'dalam_transfer' => $k['transfer'],
                'kurang' => round($selisih, 2),
                'stok_asal' => round($stokAsal, 2),
                'stok_lain' => $lain['total'],
                'gudang_lain' => $lain['gudang'],
                'saran' => (float) max(0, $saran),
                'status' => $status,
            ];
        }

        // Shortage for a resep that already exists first, then the largest shortage
        usort($hasil['rows'], fn ($a, $b) => [$b['pasti'] > 0, $b['kurang']] <=> [$a['pasti'] > 0, $a['kurang']]);
        $hasil['ringkasan']['obat_kurang'] = count($hasil['rows']);

        return $hasil;
    }

    /**
     * Need per obat of the registered visits in the date range:
     * [[obat_id => ['pasti' => float, 'prediksi' => float, 'kunjungan' => [visitation_id => true]]], ringkasan].
     */
    private function kebutuhanKunjungan(Carbon $mulai, Carbon $sampai): array
    {
        $ringkasan = ['kunjungan' => 0, 'dengan_resep' => 0, 'diprediksi' => 0, 'tanpa_riwayat' => 0, 'obat_kurang' => 0];
        $butuh = [];
        $tambah = function ($obatId, string $jenis, float $jumlah, $visitationId) use (&$butuh) {
            if ($jumlah <= 0) {
                return;
            }
            $butuh[$obatId] ??= ['pasti' => 0.0, 'prediksi' => 0.0, 'kunjungan' => []];
            $butuh[$obatId][$jenis] += $jumlah;
            $butuh[$obatId]['kunjungan'][$visitationId] = true;
        };

        $visits = DB::table('erm_visitations')
            ->whereBetween('tanggal_visitation', [$mulai->toDateString(), $sampai->toDateString()])
            ->where('status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN)
            ->get(['id', 'pasien_id', 'klinik_id', 'status_kunjungan'])
            ->keyBy('id');
        if ($visits->isEmpty()) {
            return [$butuh, $ringkasan];
        }

        // Paid visits are done: their stok has already left the gudang
        $lunas = [];
        foreach ($visits->keys()->chunk(1000) as $ids) {
            $lunas += DB::table('finance_invoices')
                ->whereIn('visitation_id', $ids->all())
                ->whereNotNull('payment_method')
                ->where('payment_method', '!=', '')
                ->pluck('visitation_id', 'visitation_id')
                ->all();
        }
        $visits = $visits->reject(fn ($v) => isset($lunas[$v->id]));
        $ringkasan['kunjungan'] = $visits->count();
        if ($visits->isEmpty()) {
            return [$butuh, $ringkasan];
        }

        // 1. Visits that already have a resep: farmasi's rows, else the dokter's
        $with = ['obat' => fn ($q) => $q->withInactive()->select('id', 'dosis'), 'wadah:id,nama'];
        $kolom = ['id', 'visitation_id', 'obat_id', 'jumlah', 'dosis', 'bungkus', 'racikan_ke', 'wadah_id'];
        $adaResep = [];
        foreach ([ResepFarmasi::class, ResepDokter::class] as $model) {
            $sisa = $visits->keys()->reject(fn ($id) => isset($adaResep[$id]));
            $baru = [];
            foreach ($sisa->chunk(1000) as $ids) {
                foreach ($model::whereIn('visitation_id', $ids->all())->with($with)->get($kolom) as $resep) {
                    $tambah($resep->obat_id, 'pasti', self::kebutuhanResep($resep), $resep->visitation_id);
                    $baru[$resep->visitation_id] = true;
                }
            }
            $adaResep += $baru;
        }
        $ringkasan['dengan_resep'] = count($adaResep);

        // 2. Not examined yet and no resep: predict from the patient's own history
        $menunggu = $visits->filter(fn ($v) => !isset($adaResep[$v->id]) && (int) $v->status_kunjungan !== 2 && $v->pasien_id);
        if ($menunggu->isEmpty()) {
            return [$butuh, $ringkasan];
        }

        // Last visits with a resep per patient + klinik (newest first), found through the (pasien, klinik, tanggal) index
        $riwayat = [];
        foreach ($menunggu->pluck('pasien_id')->unique()->chunk(500) as $pasienIds) {
            $rows = DB::table('erm_visitations as v')
                ->whereIn('v.pasien_id', $pasienIds->all())
                ->where('v.tanggal_visitation', '<', $mulai->toDateString())
                ->where('v.tanggal_visitation', '>=', $mulai->copy()->subDays(self::RIWAYAT_MAKS_HARI)->toDateString())
                ->where('v.status_kunjungan', 2)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))->from('erm_resepfarmasi as r')
                        ->whereColumn('r.visitation_id', 'v.id')->whereNull('r.deleted_at');
                })
                ->orderByDesc('v.tanggal_visitation')
                ->orderByDesc('v.id')
                ->get(['v.id', 'v.pasien_id', 'v.klinik_id']);
            foreach ($rows as $row) {
                $key = $row->pasien_id . '|' . $row->klinik_id;
                if (count($riwayat[$key] ?? []) < self::RIWAYAT_KUNJUNGAN) {
                    $riwayat[$key][] = $row->id;
                }
            }
        }

        // What those visits took out, per visit and obat (jumlah_racikan is stored when farmasi submits)
        $isi = [];
        $riwayatIds = $riwayat ? array_merge(...array_values($riwayat)) : [];
        foreach (array_chunk($riwayatIds, 1000) as $ids) {
            $rows = DB::table('erm_resepfarmasi')
                ->whereIn('visitation_id', $ids)
                ->whereNull('deleted_at')
                ->groupBy('visitation_id', 'obat_id')
                ->selectRaw('visitation_id, obat_id, SUM(CASE WHEN racikan_ke IS NULL THEN COALESCE(jumlah, 0) ELSE COALESCE(jumlah_racikan, 0) END) as qty')
                ->get();
            foreach ($rows as $row) {
                $isi[$row->visitation_id][$row->obat_id] = (float) $row->qty;
            }
        }

        foreach ($menunggu as $visit) {
            $lalu = $riwayat[$visit->pasien_id . '|' . $visit->klinik_id] ?? [];
            if (!$lalu) {
                $ringkasan['tanpa_riwayat']++;
                continue;
            }
            $ringkasan['diprediksi']++;
            $total = [];
            foreach ($lalu as $visitationId) {
                foreach ($isi[$visitationId] ?? [] as $obatId => $qty) {
                    $total[$obatId] = ($total[$obatId] ?? 0) + $qty;
                }
            }
            foreach ($total as $obatId => $qty) {
                $tambah($obatId, 'prediksi', $qty / count($lalu), $visit->id);
            }
        }

        return [$butuh, $ringkasan];
    }
}
