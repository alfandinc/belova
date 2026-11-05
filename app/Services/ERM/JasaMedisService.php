<?php

namespace App\Services\ERM;

use App\Models\ERM\Dokter;
use App\Models\ERM\JasaMedis;
use App\Models\ERM\JasaMedisTarif;
use App\Models\ERM\RiwayatTindakan;
use App\Models\ERM\Spk;
use App\Models\ERM\SpkPetugas;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use App\Models\HRD\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SPK petugas and jasa medis. Each tindakan (kode tindakan) of a riwayat's paket gets its petugas: dokter
 * and/or employees. The tarif of a tipe (dokter / employee) is split evenly over the petugas of that tipe.
 * The tarif of an SPK is the version (erm_jasa_medis_tarif) in force on its tanggal tindakan: a tarif change
 * has a berlaku mulai date and only re-prices SPK from that date. SPK stays editable after payment (it is
 * filled at the end of the shift, after the kasir). SPK is filled at the end of each shift (no jam): a
 * row counts in the rekap once its invoice is paid.
 */
class JasaMedisService
{
    public const TIPES = [SpkPetugas::TIPE_DOKTER, SpkPetugas::TIPE_EMPLOYEE];

    public const INVOICE_LABELS = [
        'paid' => 'Lunas',
        'partial' => 'Bayar sebagian',
        'issued' => 'Belum dibayar',
        null => 'Belum ada invoice',
    ];

    /**
     * SQL (with bindings) of the invoice status of the riwayat (alias $r): the best status (paid > partial > issued)
     * of the invoices holding its own invoice item, or, when it has none, of its visitation's invoices; NULL = no invoice.
     */
    public static function invoiceStatusSql(string $r = 'r'): array
    {
        $type = RiwayatTindakan::class;
        $own = "FROM finance_invoice_items ii JOIN finance_invoices i ON i.id = ii.invoice_id"
            . " WHERE ii.billable_type = ? AND ii.billable_id = {$r}.id";

        $sql = "(CASE WHEN EXISTS (SELECT 1 {$own})"
            . " THEN (SELECT i.status {$own} ORDER BY FIELD(i.status, 'issued', 'partial', 'paid') DESC LIMIT 1)"
            . " ELSE (SELECT vi.status FROM finance_invoices vi WHERE vi.visitation_id = {$r}.visitation_id"
            . " ORDER BY FIELD(vi.status, 'issued', 'partial', 'paid') DESC LIMIT 1) END)";

        return [$sql, [$type, $type]];
    }

    public static function invoiceStatus(RiwayatTindakan $riwayat): ?string
    {
        [$sql, $bindings] = self::invoiceStatusSql('r');

        return DB::table('erm_riwayat_tindakan as r')->where('r.id', $riwayat->id)
            ->selectRaw("{$sql} as invoice_status", $bindings)->value('invoice_status');
    }

    /** Invoice of each visit (best status first when a visit has several): [visitation_id => {invoice_number, status}] */
    public static function visitInvoices(array $visitationIds): Collection
    {
        if (!$visitationIds) {
            return collect();
        }

        return DB::table('finance_invoices')->whereIn('visitation_id', $visitationIds)
            ->orderByRaw("FIELD(status, 'issued', 'partial', 'paid') DESC")->orderByDesc('id')
            ->get(['visitation_id', 'invoice_number', 'status'])
            ->groupBy('visitation_id')->map->first();
    }

    public static function invoiceLabel(?string $status): string
    {
        return self::INVOICE_LABELS[$status] ?? ucfirst((string) $status);
    }

    /** Tindakan of the riwayat: those in its paket now, plus any that already have petugas (paket changed later) */
    public static function kodeTindakans(RiwayatTindakan $riwayat): Collection
    {
        $ids = DB::table('erm_tindakan_kode_tindakan')->where('tindakan_id', $riwayat->tindakan_id)->pluck('kode_tindakan_id')
            ->merge(SpkPetugas::where('riwayat_tindakan_id', $riwayat->id)->pluck('kode_tindakan_id'))
            ->unique()->values();

        return \App\Models\ERM\KodeTindakan::with('jasaMedis')->whereIn('id', $ids)->orderBy('nama')->get();
    }

    /** Subquery: how many tindakan of the riwayat's (alias r) current paket have petugas */
    public static function terisiSubquery()
    {
        return DB::table('erm_spk_petugas as sp')
            ->selectRaw('COUNT(DISTINCT sp.kode_tindakan_id)')
            ->whereColumn('sp.riwayat_tindakan_id', 'r.id')
            ->whereIn('sp.kode_tindakan_id', DB::table('erm_tindakan_kode_tindakan as tk2')->select('tk2.kode_tindakan_id')->whereColumn('tk2.tindakan_id', 'r.tindakan_id'));
    }

    public static function dokterOptions(): Collection
    {
        return Dokter::active()->with('user:id,name')->get()
            ->map(fn ($d) => ['id' => $d->id, 'nama' => $d->user->name ?? ('Dokter #' . $d->id)])
            ->sortBy('nama')->values();
    }

    /** Employees who do tindakan: jabatan (HRD position) or user role Perawat / Beautician */
    public const PETUGAS_JABATAN = ['Perawat', 'Beautician'];

    public static function employeeOptions(): Collection
    {
        return Employee::query()
            ->where(fn ($q) => $q->whereHas('positions', fn ($p) => $p->whereIn('name', self::PETUGAS_JABATAN))
                ->orWhereHas('user.roles', fn ($r) => $r->whereIn('name', self::PETUGAS_JABATAN)))
            ->orderBy('nama')->get(['id', 'nama'])
            ->map(fn ($e) => ['id' => $e->id, 'nama' => $e->nama])->values();
    }

    /** SPK form of a whole visit (one invoice): header + every paket tindakan with its tindakan and petugas */
    public function visitFormData(Visitation $visitation): array
    {
        $visitation->loadMissing(['pasien', 'dokter.user', 'klinik']);
        $riwayats = RiwayatTindakan::with(['tindakan', 'spk', 'visitation.pasien', 'visitation.dokter.user', 'visitation.klinik'])
            ->where('visitation_id', $visitation->id)->orderBy('id')->get();
        $invoice = self::visitInvoices([$visitation->id])->first();
        $forms = $riwayats->map(fn ($r) => $this->formData($r));
        $spk = $riwayats->pluck('spk')->filter();

        return [
            'visitation_id' => $visitation->id,
            'pasien_nama' => $visitation->pasien->nama ?? '-',
            'pasien_id' => $visitation->pasien_id,
            'klinik' => $visitation->klinik->nama ?? null,
            'dokter_pj' => $visitation->dokter ? ['id' => $visitation->dokter->id, 'nama' => $visitation->dokter->user->name ?? '-'] : null,
            'invoice_number' => $invoice->invoice_number ?? null,
            'invoice' => self::invoiceLabel($invoice->status ?? null),
            'tanggal_tindakan' => optional($spk->first())->tanggal_tindakan?->toDateString()
                ?? Carbon::parse($visitation->tanggal_visitation)->toDateString(),
            'updated_at' => optional($spk->sortByDesc('updated_at')->first())->updated_at?->format('d M Y H:i'),
            'pakets' => $forms->map(fn ($f) => [
                'riwayat_id' => $f['riwayat_id'],
                'paket' => $f['paket'],
                'has_petugas' => $f['has_petugas'],
                'tindakans' => $f['tindakans'],
            ])->values(),
        ];
    }

    /** Everything the SPK form needs for one riwayat */
    public function formData(RiwayatTindakan $riwayat): array
    {
        $riwayat->loadMissing(['visitation.pasien', 'visitation.dokter.user', 'visitation.klinik', 'tindakan', 'spk']);
        $petugas = SpkPetugas::with(['dokter.user', 'employee'])->where('riwayat_tindakan_id', $riwayat->id)->get()
            ->groupBy('kode_tindakan_id');
        $spk = $riwayat->spk;
        $visit = $riwayat->visitation;

        return [
            'riwayat_id' => $riwayat->id,
            'pasien_nama' => $visit->pasien->nama ?? '-',
            'pasien_id' => $visit->pasien_id ?? null,
            'klinik' => $visit->klinik->nama ?? null,
            'tanggal_visitation' => optional($visit->tanggal_visitation ? \Carbon\Carbon::parse($visit->tanggal_visitation) : null)->toDateString(),
            'paket' => $riwayat->tindakan->nama ?? '-',
            'dokter_pj' => $visit && $visit->dokter ? ['id' => $visit->dokter->id, 'nama' => $visit->dokter->user->name ?? '-'] : null,
            'spk' => [
                'tanggal_tindakan' => $spk && $spk->tanggal_tindakan ? $spk->tanggal_tindakan->toDateString() : null,
                'updated_at' => $spk && $spk->updated_at ? $spk->updated_at->format('d M Y H:i') : null,
            ],
            'has_petugas' => $petugas->isNotEmpty(),
            // No rupiah here: the SPK form only records who did the tindakan
            'tindakans' => self::kodeTindakans($riwayat)->map(function ($k) use ($petugas) {
                $rows = $petugas->get($k->id, collect());
                $list = fn ($tipe) => $rows->where('tipe', $tipe)->map(fn ($p) => [
                    'id' => $tipe === SpkPetugas::TIPE_DOKTER ? $p->dokter_id : $p->employee_id,
                    'nama' => $tipe === SpkPetugas::TIPE_DOKTER ? ($p->dokter->user->name ?? '-') : ($p->employee->nama ?? '-'),
                ])->values();

                return [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'dokter' => $list(SpkPetugas::TIPE_DOKTER),
                    'employee' => $list(SpkPetugas::TIPE_EMPLOYEE),
                ];
            })->values(),
        ];
    }

    /**
     * Save SPK tanggal + petugas. $petugas = [kode_tindakan_id => ['dokter' => [ids], 'employee' => [ids]]].
     * SPK is filled after the shift, so every tindakan of the paket needs at least one petugas. A paket without
     * tindakan has nothing to record and is skipped (null), so it never blocks the other paket of the visit.
     */
    public function save(RiwayatTindakan $riwayat, array $data, array $petugas, ?int $userId): ?Spk
    {
        $riwayat->loadMissing(['visitation.dokter', 'tindakan']);
        $kodes = self::kodeTindakans($riwayat)->keyBy('id');
        if ($kodes->isEmpty()) {
            return null;
        }

        $clean = [];
        foreach ($petugas as $kodeId => $byTipe) {
            if (!$kodes->has((int) $kodeId)) {
                throw ValidationException::withMessages(['petugas' => 'Tindakan tidak termasuk dalam paket ini.']);
            }
            foreach (self::TIPES as $tipe) {
                $ids = collect($byTipe[$tipe] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
                if ($ids->isNotEmpty()) {
                    $clean[(int) $kodeId][$tipe] = $ids->all();
                }
            }
        }

        $this->assertPetugasExist($clean);

        $paket = $riwayat->tindakan->nama ?? 'Paket';
        $kosong = $kodes->filter(fn ($k) => empty($clean[$k->id]))->pluck('nama');
        if ($kosong->isNotEmpty()) {
            throw ValidationException::withMessages(['petugas' => "{$paket}: isi petugas untuk " . $kosong->join(', ') . '.']);
        }

        return DB::transaction(function () use ($riwayat, $data, $clean, $kodes, $userId) {
            $visit = $riwayat->visitation;
            $tanggal = $data['tanggal_tindakan'] ?? now()->toDateString();
            if ($visit->dokter_id) {
                $spk = Spk::updateOrCreate(['riwayat_tindakan_id' => $riwayat->id], [
                    'visitation_id' => $visit->id,
                    'pasien_id' => $visit->pasien_id,
                    'tindakan_id' => $riwayat->tindakan_id,
                    'dokter_id' => $visit->dokter_id,
                    'tanggal_tindakan' => $tanggal,
                ]);
            } else {
                // erm_spk.dokter_id is required: a visit without dokter only updates an existing SPK's date
                // (without SPK the rekap uses the visit date)
                $spk = Spk::where('riwayat_tindakan_id', $riwayat->id)->first();
                $spk?->update(['tanggal_tindakan' => $tanggal]);
            }

            // Tarif in force on the SPK's tanggal tindakan (the rekap's date: SPK date, else the visit date)
            $tanggalTarif = $spk ? $spk->tanggal_tindakan->toDateString() : Carbon::parse($visit->tanggal_visitation)->toDateString();
            $versions = self::tarifVersions($kodes->keys()->all());

            $existing = SpkPetugas::where('riwayat_tindakan_id', $riwayat->id)->lockForUpdate()->get();

            foreach ($kodes as $kode) {
                foreach (self::TIPES as $tipe) {
                    $rows = $existing->where('kode_tindakan_id', $kode->id)->where('tipe', $tipe);
                    $ids = $clean[$kode->id][$tipe] ?? [];
                    $column = $tipe === SpkPetugas::TIPE_DOKTER ? 'dokter_id' : 'employee_id';

                    $tarif = self::tarifOn($versions->get($kode->id), $tanggalTarif, $tipe);

                    SpkPetugas::whereIn('id', $rows->filter(fn ($p) => !in_array((int) $p->{$column}, $ids, true))->pluck('id'))->delete();

                    if (!$ids) {
                        continue;
                    }
                    $nominal = round($tarif / count($ids), 2);
                    foreach ($ids as $id) {
                        $row = $rows->first(fn ($p) => (int) $p->{$column} === $id);
                        $values = ['tarif' => $tarif, 'jumlah_petugas' => count($ids), 'nominal' => $nominal];
                        if ($row) {
                            $row->update($values);
                        } else {
                            SpkPetugas::create($values + [
                                'riwayat_tindakan_id' => $riwayat->id,
                                'kode_tindakan_id' => $kode->id,
                                'tipe' => $tipe,
                                $column => $id,
                                'created_by' => $userId,
                            ]);
                        }
                    }
                }
            }

            return $spk;
        });
    }

    // ------------------------------------------------------------------ tarif versions

    /** Tarif versions per tindakan, newest first: [kode_tindakan_id => Collection<JasaMedisTarif>] */
    public static function tarifVersions(array $kodeTindakanIds): Collection
    {
        return JasaMedisTarif::whereIn('kode_tindakan_id', $kodeTindakanIds)
            ->orderByDesc('berlaku_mulai')->orderByDesc('id')
            ->get()->groupBy('kode_tindakan_id');
    }

    /** Tarif of a tipe in force on $tanggal: the newest version with berlaku_mulai <= $tanggal (0 when none) */
    public static function tarifOn(?Collection $versions, string $tanggal, string $tipe): float
    {
        $version = $versions?->first(fn ($v) => $v->berlaku_mulai->toDateString() <= $tanggal);

        return $version ? $version->tarif($tipe) : 0.0;
    }

    /**
     * Change the tarif of a tindakan from $berlakuMulai (max today): records the version (= the log), keeps
     * erm_jasa_medis on today's tarif and re-prices the petugas of SPK dated on / after $berlakuMulai.
     * Returns the version, or null when nothing changed.
     */
    public function setTarif(int $kodeTindakanId, float $dokter, float $petugas, ?string $keterangan, string $berlakuMulai, string $sumber, ?int $userId): ?JasaMedisTarif
    {
        return DB::transaction(function () use ($kodeTindakanId, $dokter, $petugas, $keterangan, $berlakuMulai, $sumber, $userId) {
            $versions = self::tarifVersions([$kodeTindakanId])->get($kodeTindakanId);
            $dokterLama = self::tarifOn($versions, $berlakuMulai, SpkPetugas::TIPE_DOKTER);
            $petugasLama = self::tarifOn($versions, $berlakuMulai, SpkPetugas::TIPE_EMPLOYEE);
            $keteranganLama = JasaMedis::where('kode_tindakan_id', $kodeTindakanId)->value('keterangan');
            $adaVersi = (bool) $versions?->first(fn ($v) => $v->berlaku_mulai->toDateString() <= $berlakuMulai);

            if ($adaVersi && $dokterLama === $dokter && $petugasLama === $petugas && (string) $keteranganLama === (string) $keterangan) {
                return null;
            }

            $version = JasaMedisTarif::create([
                'kode_tindakan_id' => $kodeTindakanId,
                'berlaku_mulai' => $berlakuMulai,
                'tarif_dokter' => $dokter,
                'tarif_petugas' => $petugas,
                'tarif_dokter_lama' => $adaVersi ? $dokterLama : null,
                'tarif_petugas_lama' => $adaVersi ? $petugasLama : null,
                'keterangan' => $keterangan,
                'sumber' => $sumber,
                'user_id' => $userId,
            ]);

            // Shown in the master table: the tarif in force today
            $versions = self::tarifVersions([$kodeTindakanId])->get($kodeTindakanId);
            $today = now()->toDateString();
            JasaMedis::updateOrCreate(['kode_tindakan_id' => $kodeTindakanId], [
                'tarif_dokter' => self::tarifOn($versions, $today, SpkPetugas::TIPE_DOKTER),
                'tarif_employee' => self::tarifOn($versions, $today, SpkPetugas::TIPE_EMPLOYEE),
                'keterangan' => $keterangan,
                'updated_by' => $userId,
            ]);

            $version->update(['spk_diperbarui' => self::repriceFrom($kodeTindakanId, $berlakuMulai, $versions)]);

            return $version;
        });
    }

    /** Petugas of the tindakan on SPK dated on / after $from take the tarif in force on their date. Returns rows changed. */
    private static function repriceFrom(int $kodeTindakanId, string $from, Collection $versions): int
    {
        $tanggal = 'DATE(COALESCE(s.tanggal_tindakan, v.tanggal_visitation))';
        $rows = DB::table('erm_spk_petugas as p')
            ->join('erm_riwayat_tindakan as r', 'r.id', '=', 'p.riwayat_tindakan_id')
            ->join('erm_visitations as v', 'v.id', '=', 'r.visitation_id')
            ->leftJoin('erm_spk as s', 's.riwayat_tindakan_id', '=', 'r.id')
            ->where('p.kode_tindakan_id', $kodeTindakanId)
            ->whereRaw("{$tanggal} >= ?", [$from])
            ->select('p.id', 'p.tipe', 'p.tarif', 'p.jumlah_petugas')
            ->selectRaw("{$tanggal} as tanggal")
            ->get();

        $updated = 0;
        foreach ($rows as $p) {
            $tarif = self::tarifOn($versions, $p->tanggal, $p->tipe);
            if (abs((float) $p->tarif - $tarif) < 0.005) {
                continue;
            }
            SpkPetugas::whereKey($p->id)->update([
                'tarif' => $tarif,
                'nominal' => round($tarif / max(1, (int) $p->jumlah_petugas), 2),
            ]);
            $updated++;
        }

        return $updated;
    }

    private function assertPetugasExist(array $clean): void
    {
        // filter(): a tindakan done by only one tipe has no list for the other (pluck gives null)
        $dokterIds = collect($clean)->pluck(SpkPetugas::TIPE_DOKTER)->flatten()->filter()->unique();
        $employeeIds = collect($clean)->pluck(SpkPetugas::TIPE_EMPLOYEE)->flatten()->filter()->unique();

        if ($dokterIds->count() !== Dokter::whereIn('id', $dokterIds)->count()
            || $employeeIds->count() !== Employee::withInactive()->whereIn('id', $employeeIds)->count()) {
            throw ValidationException::withMessages(['petugas' => 'Ada petugas yang tidak ditemukan, muat ulang halaman.']);
        }
    }

    /**
     * Jasa medis rows of a period (tanggal tindakan of the SPK, else the visit date). eligible = invoice paid;
     * the others are "tertunda".
     */
    public function rekapRows(string $from, string $to, ?int $klinikId = null, ?string $tipe = null): Collection
    {
        [$invoiceSql, $bindings] = self::invoiceStatusSql('r');
        $tanggal = 'DATE(COALESCE(s.tanggal_tindakan, v.tanggal_visitation))';

        return DB::table('erm_spk_petugas as p')
            ->join('erm_riwayat_tindakan as r', 'r.id', '=', 'p.riwayat_tindakan_id')
            ->join('erm_visitations as v', 'v.id', '=', 'r.visitation_id')
            ->leftJoin('erm_spk as s', 's.riwayat_tindakan_id', '=', 'r.id')
            ->leftJoin('erm_pasiens as ps', 'ps.id', '=', 'v.pasien_id')
            ->leftJoin('erm_tindakan as t', 't.id', '=', 'r.tindakan_id')
            ->leftJoin('erm_klinik as kl', 'kl.id', '=', 'v.klinik_id')
            ->join('erm_kode_tindakan as k', 'k.id', '=', 'p.kode_tindakan_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'p.dokter_id')
            ->leftJoin('users as du', 'du.id', '=', 'd.user_id')
            ->leftJoin('hrd_employee as e', 'e.id', '=', 'p.employee_id')
            ->whereRaw("{$tanggal} BETWEEN ? AND ?", [$from, $to])
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId))
            ->when($tipe, fn ($q) => $q->where('p.tipe', $tipe))
            ->orderByRaw($tanggal)->orderBy('r.id')
            ->select('p.id', 'p.tipe', 'p.dokter_id', 'p.employee_id', 'p.tarif', 'p.jumlah_petugas', 'p.nominal',
                'r.id as riwayat_id', 'v.pasien_id', 'ps.nama as pasien_nama', 't.nama as paket', 'k.nama as tindakan',
                'kl.nama as klinik')
            ->selectRaw("{$tanggal} as tanggal")
            ->selectRaw("CASE WHEN p.tipe = 'dokter' THEN du.name ELSE e.nama END as penerima")
            ->selectRaw("{$invoiceSql} as invoice_status", $bindings)
            ->get()
            ->map(function ($row) {
                $row->eligible = $row->invoice_status === 'paid';
                $row->invoice = self::invoiceLabel($row->invoice_status);
                $row->penerima_key = $row->tipe . ':' . ($row->tipe === SpkPetugas::TIPE_DOKTER ? $row->dokter_id : $row->employee_id);
                $row->nominal = (float) $row->nominal;
                $row->tarif = (float) $row->tarif;

                return $row;
            });
    }

    /** One line per penerima of eligible rows: jumlah tindakan and total jasa medis */
    public static function ringkasan(Collection $rows): Collection
    {
        return $rows->where('eligible', true)->groupBy('penerima_key')->map(fn ($g) => (object) [
            'penerima_key' => $g->first()->penerima_key,
            'tipe' => $g->first()->tipe,
            'penerima' => $g->first()->penerima ?? '-',
            'jumlah' => $g->count(),
            'pasien' => $g->pluck('pasien_id')->unique()->count(),
            'total' => round($g->sum('nominal'), 2),
        ])->sortBy([['tipe', 'asc'], ['penerima', 'asc']])->values();
    }
}
