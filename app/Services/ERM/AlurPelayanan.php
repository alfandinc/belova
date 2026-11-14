<?php

namespace App\Services\ERM;

use App\Models\ERM\Dokter;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Tahap Pelayanan": where a visit currently is in the service flow, shared by Rawat Jalan, Billing and E-Resep
 * so every unit reads the same stage. Derived from existing data only (no stored status):
 *
 *   menunggu_perawat -> menunggu_dokter -> diperiksa_dokter -> tindakan -> menunggu_farmasi -> menunggu_kasir
 *   -> menunggu_hasil_lab -> selesai
 *
 * tindakan = done at the dokter, has tindakan, not paid yet. There is no live "tindakan finished" signal
 * (SPK is filled after the shift), so it lasts until the kasir transaction and wins over a pending resep.
 *
 * Farmasi comes before the kasir because the farmasi's resep submit is what puts the obat on the bill.
 * A visit paid before its resep was submitted stays at menunggu_farmasi (the farmasi still has to finish it).
 */
class AlurPelayanan
{
    /** Ordered stages: key => [label, colour, icon, what the stage means] */
    public const STAGES = [
        'menunggu_perawat' => ['Menunggu Perawat', '#64748b', 'fas fa-user-nurse', 'Sudah terdaftar, asesmen perawat belum diisi'],
        'sedang_dilayani' => ['Sedang Dilayani', '#475569', 'fas fa-concierge-bell', 'Kunjungan non-klinis yang belum selesai, atau belum ada item tagihan (belum muncul di Billing)'],
        'menunggu_dokter' => ['Menunggu Dokter', '#d39e00', 'fas fa-hourglass-half', 'Asesmen perawat selesai, pasien belum masuk ruang dokter'],
        'diperiksa_dokter' => ['Diperiksa Dokter', '#1d63ed', 'fas fa-user-md', 'Pasien di ruang dokter, pemeriksaan belum disimpan'],
        'tindakan' => ['Menjalani Tindakan', '#db2777', 'fas fa-procedures', 'Ada tindakan dan belum dibayar (termasuk yang sudah selesai ditindak dan sedang ke kasir)'],
        'menunggu_farmasi' => ['Menunggu Farmasi', '#6f42c1', 'fas fa-prescription-bottle-alt', 'Resep belum disubmit farmasi'],
        'menunggu_kasir' => ['Menunggu Kasir', '#e8590c', 'fas fa-cash-register', 'Tagihan lengkap, belum ditransaksikan kasir'],
        'menunggu_hasil_lab' => ['Menunggu Hasil Lab', '#0891b2', 'fas fa-flask', 'Sudah dibayar, hasil lab belum keluar'],
        'selesai' => ['Selesai', '#20a374', 'fas fa-check-circle', 'Sudah dibayar, resep dilayani, hasil lab (jika ada) keluar'],
        'dibatalkan' => ['Dibatalkan', '#6c757d', 'fas fa-ban', 'Kunjungan dibatalkan'],
    ];

    /** Waiting longer than this (minutes, today) is flagged red. */
    public const LATE_MINUTES = 30;

    /** Stages shown in the strip, in flow order (sedang_dilayani only when there is one). */
    public const STRIP_STAGES = ['menunggu_perawat', 'sedang_dilayani', 'menunggu_dokter', 'diperiksa_dokter', 'tindakan', 'menunggu_farmasi', 'menunggu_kasir', 'menunggu_hasil_lab', 'selesai'];

    /** SQL CASE giving the stage key of the visit aliased $v. */
    public static function stageSql(string $v = 'v'): string
    {
        $clinical = implode(',', Visitation::CLINICAL_TYPES);
        $masuk = Visitation::tracksMasukRuangan() ? "{$v}.masuk_ruangan_at IS NOT NULL" : '1 = 0';

        return "(CASE"
            . " WHEN {$v}.status_kunjungan = " . Visitation::STATUS_DIBATALKAN . " THEN 'dibatalkan'"
            . " WHEN {$v}.status_kunjungan <> 2 AND {$v}.jenis_kunjungan NOT IN ({$clinical}) THEN 'sedang_dilayani'"
            . " WHEN {$v}.status_kunjungan <> 2 AND COALESCE({$v}.status_dokumen, '') = '' THEN 'menunggu_perawat'"
            . " WHEN {$v}.status_kunjungan <> 2 AND NOT ({$masuk}) THEN 'menunggu_dokter'"
            . " WHEN {$v}.status_kunjungan <> 2 THEN 'diperiksa_dokter'"
            // No live "tindakan done" signal (SPK is filled after the shift): a visit with tindakan stays here until paid
            . " WHEN EXISTS (SELECT 1 FROM erm_riwayat_tindakan ap_rt WHERE ap_rt.visitation_id = {$v}.id)"
            . " AND NOT " . self::transactedSql($v) . " THEN 'tindakan'"
            . " WHEN " . self::hasResepSql($v) . " AND NOT " . self::resepDoneSql($v) . " THEN 'menunggu_farmasi'"
            // Nothing on the bill yet (e.g. produk visit without items): the kasir has nothing to process / Billing does not list it
            . " WHEN NOT " . self::transactedSql($v) . " AND NOT " . self::hasBillSql($v) . " THEN 'sedang_dilayani'"
            . " WHEN NOT " . self::transactedSql($v) . " THEN 'menunggu_kasir'"
            . " WHEN " . self::labOpenSql($v) . " THEN 'menunggu_hasil_lab'"
            . " ELSE 'selesai' END)";
    }

    /** The visit has a lab request that is neither completed nor cancelled. */
    public static function labOpenSql(string $v = 'v'): string
    {
        return "EXISTS (SELECT 1 FROM erm_lab_permintaan ap_lp WHERE ap_lp.visitation_id = {$v}.id"
            . " AND COALESCE(ap_lp.status, '') NOT IN ('completed', 'cancelled') AND ap_lp.cancelled_at IS NULL)";
    }

    /** Kasir processed the invoice (any payment method, incl. piutang / asuransi). */
    public static function transactedSql(string $v = 'v'): string
    {
        return "EXISTS (SELECT 1 FROM finance_invoices ap_fi WHERE ap_fi.visitation_id = {$v}.id"
            . " AND " . self::invoiceTransactedSql('ap_fi') . ")";
    }

    /** The kasir processed this visit's invoice (same rule as the stages). */
    public static function isTransacted(string $visitationId): bool
    {
        return DB::table('erm_visitations as v')->where('v.id', $visitationId)
            ->whereRaw(self::transactedSql('v'))->exists();
    }

    /**
     * Invoice $i was processed by the kasir — same rule as the Billing row label / "Belum Transaksi" card:
     * something paid, a piutang / asuransi invoice, or a Rp 0 invoice marked paid.
     */
    public static function invoiceTransactedSql(string $i): string
    {
        return "(COALESCE({$i}.amount_paid, 0) > 0"
            . " OR LOWER(COALESCE({$i}.payment_method, '')) = 'piutang'"
            . " OR LOWER(COALESCE({$i}.payment_method, '')) LIKE 'asuransi\\_%'"
            . " OR (COALESCE({$i}.total_amount, 0) <= 0 AND COALESCE({$i}.status, '') = 'paid'))";
    }

    /** The visit has something to pay: an active billing row (what the Billing page lists) or an invoice. */
    public static function hasBillSql(string $v = 'v'): string
    {
        return "(EXISTS (SELECT 1 FROM finance_billing ap_fb WHERE ap_fb.visitation_id = {$v}.id AND ap_fb.deleted_at IS NULL)"
            . " OR EXISTS (SELECT 1 FROM finance_invoices ap_fi2 WHERE ap_fi2.visitation_id = {$v}.id))";
    }

    /** The visit has resep items (the dokter's, or the farmasi's copy). */
    public static function hasResepSql(string $v = 'v'): string
    {
        return "(EXISTS (SELECT 1 FROM erm_resepfarmasi ap_rf WHERE ap_rf.visitation_id = {$v}.id)"
            . " OR EXISTS (SELECT 1 FROM erm_resepdokter ap_rd WHERE ap_rd.visitation_id = {$v}.id))";
    }

    /** Farmasi submitted (or marked done) the resep. */
    public static function resepDoneSql(string $v = 'v'): string
    {
        return "EXISTS (SELECT 1 FROM erm_resepdetail ap_rdt WHERE ap_rdt.visitation_id = {$v}.id AND ap_rdt.status = 1)";
    }

    /**
     * Visits of the filters (date range, klinik, dokter); cancelled visits excluded.
     * $filters: start_date, end_date, klinik_id, dokter_id (null / '' / '__all__' = all).
     */
    public static function baseQuery(array $filters)
    {
        $start = ($filters['start_date'] ?? null) ?: now()->toDateString();
        $end = ($filters['end_date'] ?? null) ?: $start;

        $query = DB::table('erm_visitations as v')
            ->whereBetween('v.tanggal_visitation', [$start, $end])
            ->where('v.status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN);

        foreach (['klinik_id', 'dokter_id'] as $key) {
            $value = $filters[$key] ?? null;
            if ($value !== null && $value !== '' && $value !== '__all__') {
                $query->where('v.' . $key, $value);
            }
        }
        // Billing page: follow its Umum / Asuransi tab
        self::applyMetodeGroup($query, $filters['metode_group'] ?? null, 'v.metode_bayar_id');

        return $query;
    }

    /**
     * Umum vs Asuransi split shared with the Billing tabs (erm_metode_bayar.is_asuransi; visits without
     * metode bayar fall under Asuransi). Uses the few metode bayar ids so MySQL keeps the date index.
     */
    public static function applyMetodeGroup($query, ?string $group, string $column = 'erm_visitations.metode_bayar_id'): void
    {
        if ($group === 'umum') {
            $query->whereIn($column, MetodeBayar::where('is_asuransi', false)->pluck('id')->all());
        } elseif ($group === 'asuransi') {
            $nonAsuransiIds = MetodeBayar::where(function ($q) {
                $q->where('is_asuransi', false)->orWhereNull('is_asuransi');
            })->pluck('id')->all();
            $query->where(function ($q) use ($column, $nonAsuransiIds) {
                $q->whereNull($column)->orWhereNotIn($column, $nonAsuransiIds);
            });
        }
    }

    /** Visit count per stage for the given visit ids query (Billing stats cards). */
    public static function countsForIds($idsQuery): array
    {
        return DB::table('erm_visitations as v')
            ->whereIn('v.id', $idsQuery)
            ->selectRaw(self::stageSql() . ' as tahap, COUNT(*) as total')
            ->groupBy('tahap')
            ->pluck('total', 'tahap')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Visit count per stage for the filters (every strip stage present, 0 when none). */
    public static function counts(array $filters): array
    {
        $rows = self::baseQuery($filters)
            ->selectRaw(self::stageSql() . ' as tahap, COUNT(*) as total')
            ->groupBy('tahap')
            ->pluck('total', 'tahap');

        $counts = [];
        foreach (self::STRIP_STAGES as $key) {
            $counts[$key] = (int) ($rows[$key] ?? 0);
        }

        return $counts;
    }

    /**
     * Per open stage, how many of today's visits (within the filters) have waited longer than LATE_MINUTES.
     * Empty when the filter range does not include today (waiting time is only measured today).
     */
    public static function lateCounts(array $filters): array
    {
        $today = now()->toDateString();
        $start = ($filters['start_date'] ?? null) ?: $today;
        $end = ($filters['end_date'] ?? null) ?: $start;
        if ($today < $start || $today > $end) {
            return [];
        }

        $rows = self::baseQuery(array_merge($filters, ['start_date' => $today, 'end_date' => $today]))
            ->select(self::visitColumns())
            ->selectRaw(self::stageSql() . ' as tahap')
            ->get()
            ->reject(fn ($r) => in_array($r->tahap, ['selesai', 'dibatalkan'], true))
            ->values();

        return self::withTimes($rows)
            ->filter(fn ($r) => $r->menit !== null && $r->menit > self::LATE_MINUTES)
            ->countBy('tahap')
            ->all();
    }

    /**
     * Stage + since-when of the given visits (table rows).
     *
     * @return array<string, array{tahap: string, sejak: ?string, menit: ?int, lab_pending: int}>
     */
    public static function forVisits(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if (!$ids) {
            return [];
        }

        $rows = DB::table('erm_visitations as v')
            ->whereIn('v.id', $ids)
            ->select(self::visitColumns())
            ->selectRaw(self::stageSql() . ' as tahap')
            ->get();

        return self::withTimes($rows)->mapWithKeys(fn ($r) => [(string) $r->id => [
            'tahap' => $r->tahap,
            'sejak' => $r->sejak,
            'menit' => $r->menit,
            'lab_pending' => $r->lab_pending,
        ]])->all();
    }

    /** Visits currently in one stage (strip click), longest waiting first. */
    public static function listStage(array $filters, string $stage): array
    {
        $rows = self::baseQuery($filters)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
            ->whereRaw(self::stageSql() . ' = ?', [$stage])
            ->select(self::visitColumns())
            ->addSelect('p.nama as nama_pasien', 'u.name as dokter', 'k.nama as klinik', 'v.no_antrian', 'v.tanggal_visitation')
            ->selectRaw(self::stageSql() . ' as tahap')
            ->limit(300)
            ->get();

        return self::withTimes($rows)
            ->map(fn ($r) => [
                'id' => $r->id,
                'no_rm' => $r->pasien_id,
                'nama_pasien' => $r->nama_pasien ?? '-',
                'dokter' => $r->dokter ?? '-',
                'klinik' => $r->klinik ?? '-',
                'jenis' => Visitation::typeLabel($r->jenis_kunjungan),
                'no_antrian' => $r->no_antrian,
                'tanggal' => Carbon::parse($r->tanggal_visitation)->format('d/m/Y'),
                'sejak' => $r->sejak,
                'menit' => $r->menit,
                'lab_pending' => $r->lab_pending,
            ])
            ->sortByDesc(fn ($r) => $r['menit'] ?? -1)
            ->values()
            ->all();
    }

    /** Times of every step of one visit, for the timeline popup. */
    public static function timeline(string $id): ?array
    {
        $row = DB::table('erm_visitations as v')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->where('v.id', $id)
            ->select(self::visitColumns())
            ->addSelect('p.nama as nama_pasien')
            ->selectRaw(self::stageSql() . ' as tahap')
            ->first();
        if (!$row) {
            return null;
        }

        $row = self::withTimes(collect([$row]))->first();
        $t = $row->times;
        $fmt = fn ($at) => $at ? Carbon::parse($at)->format('H:i') : null;
        $clinical = Visitation::isClinicalType($row->jenis_kunjungan);

        $steps = [];
        $steps[] = ['label' => 'Terdaftar', 'at' => $fmt($t['daftar'])];
        if ($clinical) {
            $steps[] = ['label' => 'Asesmen perawat', 'at' => $fmt($t['perawat'])];
            if (Visitation::tracksMasukRuangan()) {
                $steps[] = ['label' => 'Masuk ruang dokter', 'at' => $fmt($t['masuk'])];
            }
            $steps[] = ['label' => 'Selesai periksa', 'at' => $fmt($t['periksa'])];
        }
        if ($row->has_resep) {
            $steps[] = ['label' => 'Resep disubmit farmasi', 'at' => $fmt($t['resep'])];
        }
        $steps[] = ['label' => 'Transaksi kasir', 'at' => $fmt($t['bayar'])];
        if ($row->has_lab) {
            $steps[] = ['label' => 'Hasil lab selesai', 'at' => $fmt($t['lab'])];
        }

        // Minutes between consecutive filled steps
        $prev = null;
        foreach ($steps as &$step) {
            $step['durasi'] = null;
            if ($step['at']) {
                if ($prev) {
                    $step['durasi'] = max(0, (int) Carbon::createFromFormat('H:i', $prev)->diffInMinutes(Carbon::createFromFormat('H:i', $step['at']), false));
                }
                $prev = $step['at'];
            }
        }
        unset($step);

        return [
            'nama_pasien' => $row->nama_pasien ?? '-',
            'no_rm' => $row->pasien_id,
            'tahap' => $row->tahap,
            'sejak' => $row->sejak,
            'menit' => $row->menit,
            'lab_pending' => $row->lab_pending,
            'steps' => $steps,
        ];
    }

    /** Default dokter filter of Rawat Jalan: a Dokter user without an explicit filter sees their own visits. */
    public static function defaultDokterId($user, bool $hasDokterParam): ?string
    {
        if ($hasDokterParam || !$user || !$user->hasRole('Dokter')) {
            return null;
        }

        return optional(Dokter::where('user_id', $user->id)->first())->id;
    }

    private static function visitColumns(): array
    {
        $columns = ['v.id', 'v.pasien_id', 'v.jenis_kunjungan', 'v.status_kunjungan', 'v.status_dokumen', 'v.created_at', 'v.updated_at'];
        if (Visitation::tracksMasukRuangan()) {
            $columns[] = 'v.masuk_ruangan_at';
        }

        return $columns;
    }

    /**
     * Add the step times, the start of the current stage (sejak, H:i), minutes in it (menit),
     * has_resep and open lab requests, with a few WHERE IN queries.
     */
    private static function withTimes($rows)
    {
        $ids = $rows->pluck('id')->map(fn ($id) => (string) $id)->all();
        if (!$ids) {
            return $rows;
        }

        $minAt = fn (string $table, string $column = 'created_at') => DB::table($table)
            ->whereIn('visitation_id', $ids)
            ->groupBy('visitation_id')
            ->selectRaw("visitation_id, MIN({$column}) as at")
            ->pluck('at', 'visitation_id');

        $perawat = $minAt('erm_asesmen_perawats');
        $penunjang = $minAt('erm_asesmen_penunjang');
        $cppt = $minAt('erm_cppt');
        $resepAt = DB::table('erm_resepdetail')->whereIn('visitation_id', $ids)->where('status', 1)
            ->groupBy('visitation_id')->selectRaw('visitation_id, MIN(submitted_at) as at')->pluck('at', 'visitation_id');
        $bayar = DB::table('finance_invoices')->whereIn('visitation_id', $ids)
            ->whereRaw("TRIM(COALESCE(payment_method, '')) <> ''")
            ->groupBy('visitation_id')->selectRaw('visitation_id, MIN(COALESCE(payment_date, updated_at)) as at')->pluck('at', 'visitation_id');
        $resepIds = DB::table('erm_resepfarmasi')->whereIn('visitation_id', $ids)->distinct()->pluck('visitation_id')
            ->merge(DB::table('erm_resepdokter')->whereIn('visitation_id', $ids)->distinct()->pluck('visitation_id'))
            ->map(fn ($id) => (string) $id)->flip();
        // Same "open" rule as labOpenSql(); plus when the last result came out (timeline)
        $labRows = DB::table('erm_lab_permintaan')->whereIn('visitation_id', $ids)->whereNull('cancelled_at')
            ->whereRaw("COALESCE(status, '') <> 'cancelled'")
            ->groupBy('visitation_id')
            ->selectRaw("visitation_id, SUM(COALESCE(status, '') <> 'completed') as open_total, MAX(completed_at) as done_at")
            ->get()->keyBy('visitation_id');
        $labPending = $labRows->map(fn ($l) => (int) $l->open_total);

        $now = now();

        return $rows->map(function ($r) use ($perawat, $penunjang, $cppt, $resepAt, $bayar, $resepIds, $labRows, $labPending, $now) {
            $id = (string) $r->id;
            $periksa = $penunjang[$id] ?? $cppt[$id] ?? null;
            if (!$periksa && (int) $r->status_kunjungan === 2) {
                // Done from the asesmen perawat ("Tindakan saja") or a non-clinical visit
                $periksa = $perawat[$id] ?? $r->created_at;
            }

            $times = [
                'daftar' => $r->created_at,
                'perawat' => $perawat[$id] ?? null,
                'masuk' => $r->masuk_ruangan_at ?? null,
                'periksa' => $periksa,
                'resep' => $resepAt[$id] ?? null,
                'bayar' => $bayar[$id] ?? null,
                // Last lab result, only once every request of the visit is done
                'lab' => ($labRows->has($id) && (int) $labRows[$id]->open_total === 0) ? $labRows[$id]->done_at : null,
            ];
            $r->has_lab = $labRows->has($id);

            $since = match ($r->tahap) {
                'menunggu_perawat', 'sedang_dilayani' => $times['daftar'],
                'menunggu_dokter' => $times['perawat'] ?? $r->updated_at,
                'diperiksa_dokter' => $times['masuk'],
                'tindakan' => $times['periksa'],
                'menunggu_farmasi' => $times['bayar'] ?? $times['periksa'],
                'menunggu_kasir' => $times['resep'] ?? $times['periksa'],
                'menunggu_hasil_lab' => $times['bayar'],
                'selesai' => $times['lab'] ?? $times['bayar'],
                default => null,
            };

            $r->times = $times;
            $r->has_resep = isset($resepIds[$id]);
            $r->lab_pending = (int) ($labPending[$id] ?? 0);
            $sinceAt = $since ? Carbon::parse($since) : null;
            $r->sejak = $sinceAt ? ($sinceAt->isToday() ? $sinceAt->format('H:i') : $sinceAt->format('d/m H:i')) : null;
            // Waiting minutes only for open stages that started today (an old unpaid visit is not "waiting")
            $r->menit = ($sinceAt && $sinceAt->isToday() && !in_array($r->tahap, ['selesai', 'dibatalkan'], true))
                ? (int) Carbon::parse($since)->diffInMinutes($now)
                : null;

            return $r;
        });
    }
}
