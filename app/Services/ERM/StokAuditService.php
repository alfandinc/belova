<?php

namespace App\Services\ERM;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Finds stok batches whose data looks wrong: default / missing ED, unofficial batch names,
 * batches that no incoming document knows, stok that does not match the kartu stok, etc.
 * Read-only; fixes go through batch master data (name, ED) or a Penyesuaian Stok document.
 */
class StokAuditService
{
    private const CACHE_KEY = 'stok_audit.count';

    // code => [label, severity (tinggi / sedang / rendah), explanation]; rendah alone is not counted or listed by default
    public const ISSUES = [
        'ed_default' => ['ED default', 'tinggi', 'ED berupa tanggal default/sementara atau dipakai massal oleh banyak obat, kemungkinan bukan ED asli.'],
        'ed_kosong' => ['ED kosong', 'tinggi', 'Batch tidak punya tanggal kadaluarsa.'],
        'ed_tidak_wajar' => ['ED tidak wajar', 'sedang', 'ED terlalu lama di masa lalu atau terlalu jauh di masa depan.'],
        'batch_kosong' => ['Tanpa batch', 'tinggi', 'Stok tidak punya nomor batch.'],
        'batch_sementara' => ['Batch sementara', 'tinggi', 'Nama batch buatan sistem/migrasi atau data uji (MIGRATE, INITIAL, BULK, TEST, dll), bukan nomor batch pabrik.'],
        // Low: stok from the old system never had a faktur, also after its batch number is corrected
        'batch_tanpa_dokumen' => ['Batch tanpa dokumen', 'rendah', 'Nomor batch ini tidak ada di faktur pembelian, obat hibah, maupun penyesuaian stok masuk (wajar untuk stok lama dari sistem sebelumnya).'],
        'tidak_cocok_kartu' => ['Tidak cocok kartu stok', 'tinggi', 'Jumlah stok berbeda dengan saldo terakhir di kartu stok (pernah diubah tanpa transaksi).'],
        'stok_negatif' => ['Stok minus', 'tinggi', 'Jumlah stok di bawah nol.'],
        'obat_nonaktif' => ['Obat nonaktif', 'sedang', 'Obat sudah nonaktif tetapi masih ada stoknya.'],
    ];

    /**
     * Batches with at least one issue. Filters: gudang_id, issue (code).
     */
    public function temuan(array $filters = []): array
    {
        $rows = DB::table('erm_obat_stok_gudang as s')
            ->join('erm_obat as o', 'o.id', '=', 's.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', '=', 's.gudang_id')
            ->whereNull('s.deleted_at')
            ->where('s.stok', '<>', 0)
            ->when(!empty($filters['gudang_id']), function ($q) use ($filters) {
                $q->where('s.gudang_id', $filters['gudang_id']);
            })
            ->select(
                's.id', 's.obat_id', 's.gudang_id', 's.batch', 's.expiration_date', 's.stok',
                'o.nama as nama_obat', 'o.kode_obat', 'o.status_aktif', 'o.hpp',
                DB::raw("COALESCE(NULLIF(o.satuan_stok, ''), o.satuan) as satuan"),
                'g.nama as nama_gudang'
            )
            ->orderBy('o.nama')
            ->get();

        if ($rows->isEmpty()) {
            return ['data' => [], 'ringkasan' => $this->emptySummary()];
        }

        $config = config('stok.audit');
        $placeholders = array_map('strval', $config['ed_placeholder'] ?? []);
        $massThreshold = (int) ($config['ed_massal_min_obat'] ?? 10);
        $edMin = Carbon::parse($config['ed_min'] ?? '2015-01-01')->toDateString();
        $edMax = now()->addYears((int) ($config['ed_max_tahun'] ?? 10))->toDateString();
        $batchPattern = $config['batch_sementara_pattern'] ?? '/^(MIGRATE|INITIAL|BULK)\b|TEST|DUMMY|CONTOH|SAMPLE/i';

        // One ED shared by many different obat (all stok rows, not just this gudang) looks like a default
        $massEd = DB::table('erm_obat_stok_gudang')
            ->whereNull('deleted_at')
            ->where('stok', '<>', 0)
            ->whereNotNull('expiration_date')
            ->select('expiration_date', DB::raw('COUNT(DISTINCT obat_id) as n'))
            ->groupBy('expiration_date')
            ->having('n', '>=', $massThreshold)
            ->pluck('n', 'expiration_date')
            ->keys()
            ->map(function ($d) {
                return substr((string) $d, 0, 10);
            })
            ->all();

        $documented = $this->batchBerdokumen($rows->pluck('obat_id')->unique()->all());
        $saldoKartu = $this->saldoKartuTerakhir($rows->pluck('obat_id')->unique()->all());

        $data = [];
        $ringkasan = $this->emptySummary();

        foreach ($rows as $r) {
            $issues = [];
            $ed = $r->expiration_date ? substr((string) $r->expiration_date, 0, 10) : null;
            $batch = trim((string) $r->batch);
            $key = $r->obat_id . '|' . mb_strtolower($batch);

            if ($ed === null) {
                $issues[] = 'ed_kosong';
            } elseif (in_array($ed, $placeholders, true) || in_array($ed, $massEd, true)) {
                $issues[] = 'ed_default';
            } elseif ($ed < $edMin || $ed > $edMax) {
                $issues[] = 'ed_tidak_wajar';
            }

            if ($batch === '') {
                $issues[] = 'batch_kosong';
            } elseif (preg_match($batchPattern, $batch)) {
                $issues[] = 'batch_sementara';
            } elseif (!isset($documented[$key])) {
                $issues[] = 'batch_tanpa_dokumen';
            }

            $kartuKey = $r->obat_id . '|' . (int) $r->gudang_id . '|' . mb_strtolower($batch);
            $saldo = $saldoKartu[$kartuKey] ?? null;
            if ($saldo === null || abs((float) $r->stok - $saldo) > 0.009) {
                $issues[] = 'tidak_cocok_kartu';
            }

            if ((float) $r->stok < 0) {
                $issues[] = 'stok_negatif';
            }
            if ((int) $r->status_aktif !== 1) {
                $issues[] = 'obat_nonaktif';
            }

            if (!$issues) {
                continue;
            }
            foreach ($issues as $code) {
                $ringkasan[$code]++;
            }
            $serius = array_filter($issues, function ($c) {
                return self::ISSUES[$c][1] !== 'rendah';
            });
            if ($serius) {
                $ringkasan['total']++;
            }

            if (!empty($filters['issue'])) {
                if (!in_array($filters['issue'], $issues, true)) {
                    continue;
                }
            } elseif (!$serius) {
                continue;
            }

            $data[] = [
                'id' => $r->id,
                'obat_id' => $r->obat_id,
                'gudang_id' => $r->gudang_id,
                'nama_obat' => $r->nama_obat,
                'kode_obat' => $r->kode_obat,
                'satuan' => $r->satuan,
                'nama_gudang' => $r->nama_gudang,
                'batch' => $r->batch,
                'expiration_date' => $ed,
                'stok' => round((float) $r->stok, 2),
                'saldo_kartu' => $saldo,
                'nilai' => round((float) $r->stok * (float) $r->hpp, 0),
                'issues' => $issues,
                'tinggi' => count(array_filter($issues, function ($c) {
                    return self::ISSUES[$c][1] === 'tinggi';
                })),
            ];
        }

        // Most serious first, then by value
        usort($data, function ($a, $b) {
            return [$b['tinggi'], count($b['issues']), $b['nilai']] <=> [$a['tinggi'], count($a['issues']), $a['nilai']];
        });

        // The header badge counts every gudang: only an unfiltered run refreshes it
        if (empty($filters['gudang_id'])) {
            Cache::put(self::CACHE_KEY, $ringkasan['total'], now()->addMinutes(10));
        }

        return ['data' => $data, 'ringkasan' => $ringkasan];
    }

    /** Number of flagged batches for the header badge (cached for 10 minutes). */
    public function jumlah(): int
    {
        return (int) Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return $this->temuan()['ringkasan']['total'];
        });
    }

    public function lupakanCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function emptySummary(): array
    {
        return array_fill_keys(array_keys(self::ISSUES), 0) + ['total' => 0];
    }

    /** obat_id|batch pairs that came in through a document: faktur, hibah or penyesuaian masuk (new batch). */
    private function batchBerdokumen(array $obatIds): array
    {
        $set = [];
        foreach (array_chunk($obatIds, 1000) as $chunk) {
            $sources = [
                DB::table('erm_fakturbeli_items')->whereIn('obat_id', $chunk)->whereNotNull('batch')->select('obat_id', 'batch'),
                DB::table('erm_obat_hibah_items')->whereIn('obat_id', $chunk)->whereNotNull('batch')->select('obat_id', 'batch'),
                DB::table('erm_mutasi_stok_items')->whereIn('obat_id', $chunk)->where('batch_baru', 1)->whereNotNull('batch')->select('obat_id', 'batch'),
            ];
            foreach ($sources as $query) {
                foreach ($query->distinct()->get() as $row) {
                    $set[$row->obat_id . '|' . mb_strtolower(trim((string) $row->batch))] = true;
                }
            }
        }
        return $set;
    }

    /** Last kartu stok balance per obat|gudang|batch (movements only, like stok:rekonsiliasi). */
    private function saldoKartuTerakhir(array $obatIds): array
    {
        $map = [];
        foreach (array_chunk($obatIds, 1000) as $chunk) {
            $latest = DB::table('erm_kartu_stok')
                ->whereIn('obat_id', $chunk)
                ->whereIn('tipe', ['masuk', 'keluar'])
                ->select(DB::raw('MAX(id) as last_id'))
                ->groupBy('obat_id', 'gudang_id', 'batch');

            $rows = DB::table('erm_kartu_stok as k')
                ->joinSub($latest, 'l', function ($join) {
                    $join->on('l.last_id', '=', 'k.id');
                })
                ->select('k.obat_id', 'k.gudang_id', 'k.batch', 'k.stok_setelah')
                ->get();
            foreach ($rows as $row) {
                $map[$row->obat_id . '|' . (int) $row->gudang_id . '|' . mb_strtolower(trim((string) $row->batch))] = (float) $row->stok_setelah;
            }
        }
        return $map;
    }
}
