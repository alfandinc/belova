<?php

namespace App\Http\Controllers\ERM;

use App\Exports\ERM\JasaMedisRekapExport;
use App\Http\Controllers\Controller;
use App\Models\ERM\JasaMedis;
use App\Models\ERM\JasaMedisTarif;
use App\Models\ERM\KodeTindakan;
use App\Models\ERM\Klinik;
use App\Services\ERM\JasaMedisService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Jasa medis: tarif per tindakan (kode tindakan) for the dokter and for employees, and the rekap per
 * petugas of a period. A row of the rekap counts once the tindakan's invoice is paid.
 */
class JasaMedisController extends Controller
{
    public function __construct(private JasaMedisService $service)
    {
    }

    // ------------------------------------------------------------------ tarif

    public function index()
    {
        return view('erm.jasa_medis.index');
    }

    public function data()
    {
        $paket = DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_tindakan as t', 't.id', '=', 'tk.tindakan_id')
            ->where('t.is_active', 1)
            ->groupBy('tk.kode_tindakan_id')
            ->select('tk.kode_tindakan_id')
            ->selectRaw('COUNT(*) as paket_aktif');

        $rows = DB::table('erm_kode_tindakan as k')
            ->leftJoin('erm_jasa_medis as j', 'j.kode_tindakan_id', '=', 'k.id')
            ->leftJoin('users as u', 'u.id', '=', 'j.updated_by')
            ->leftJoinSub($paket, 'pk', 'pk.kode_tindakan_id', '=', 'k.id')
            ->orderBy('k.nama')
            ->get(['k.id', 'k.kode', 'k.nama', 'k.is_active', 'j.tarif_dokter', 'j.tarif_employee', 'j.keterangan', 'j.updated_at', 'u.name as updated_by', 'pk.paket_aktif'])
            ->map(function ($r) {
                $r->is_active = (bool) $r->is_active;
                $r->tarif_dokter = (float) $r->tarif_dokter;
                $r->tarif_employee = (float) $r->tarif_employee;
                $r->paket_aktif = (int) $r->paket_aktif;
                $r->ada_tarif = $r->tarif_dokter > 0 || $r->tarif_employee > 0;
                $r->updated_at = $r->updated_at ? Carbon::parse($r->updated_at)->format('d M Y') : null;

                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function save(Request $request, KodeTindakan $kodeTindakan)
    {
        $data = $request->validate([
            'tarif_dokter' => 'required|numeric|min:0|max:999999999',
            'tarif_employee' => 'required|numeric|min:0|max:999999999',
            'keterangan' => 'nullable|string|max:255',
            'berlaku_mulai' => 'required|date|before_or_equal:today',
        ], ['berlaku_mulai.before_or_equal' => 'Tanggal berlaku tidak boleh di masa depan.']);

        $version = $this->service->setTarif(
            $kodeTindakan->id, (float) $data['tarif_dokter'], (float) $data['tarif_employee'], $data['keterangan'] ?? null,
            Carbon::parse($data['berlaku_mulai'])->toDateString(), 'manual', auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => $version ? 'Tarif jasa medis ' . $kodeTindakan->nama . ' disimpan.' : 'Tidak ada perubahan tarif.',
            'spk_diperbarui' => $version->spk_diperbarui ?? 0,
            'row' => JasaMedis::where('kode_tindakan_id', $kodeTindakan->id)->first(['tarif_dokter', 'tarif_employee', 'keterangan']),
        ]);
    }

    /** Riwayat tarif (log) of one tindakan, newest first */
    public function riwayat(KodeTindakan $kodeTindakan)
    {
        $rows = JasaMedisTarif::with('user:id,name')->where('kode_tindakan_id', $kodeTindakan->id)
            ->orderByDesc('created_at')->orderByDesc('id')->get()
            ->map(fn ($t) => [
                'berlaku_mulai' => $t->sumber === 'awal' ? null : $t->berlaku_mulai->toDateString(),
                'tarif_dokter' => $t->tarif_dokter,
                'tarif_petugas' => $t->tarif_petugas,
                'tarif_dokter_lama' => $t->tarif_dokter_lama,
                'tarif_petugas_lama' => $t->tarif_petugas_lama,
                'keterangan' => $t->keterangan,
                'sumber' => $t->sumber,
                'spk_diperbarui' => $t->spk_diperbarui,
                'user' => $t->user->name ?? '-',
                'waktu' => $t->created_at ? $t->created_at->format('d M Y H:i') : '-',
            ]);

        return response()->json(['nama' => $kodeTindakan->nama, 'data' => $rows]);
    }

    /** CSV: nama (or kode) tindakan, tarif dokter, tarif employee [, keterangan]; header optional */
    public function import(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:2048',
            'berlaku_mulai' => 'required|date|before_or_equal:today',
        ], ['berlaku_mulai.before_or_equal' => 'Tanggal berlaku tidak boleh di masa depan.']);
        $berlakuMulai = Carbon::parse($request->input('berlaku_mulai'))->toDateString();

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $first = fgets($handle);
        $delimiter = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        rewind($handle);

        $kodes = KodeTindakan::get(['id', 'kode', 'nama']);
        $byKode = $kodes->filter(fn ($k) => $k->kode)->keyBy(fn ($k) => mb_strtolower(trim($k->kode)));
        $byNama = $kodes->keyBy(fn ($k) => mb_strtolower(trim($k->nama)));
        // "50.000" / "50.000,50" (Indonesian) as well as "50000" / "50000.50"
        $toNumber = function ($v) {
            $v = preg_replace('/[^0-9.,]/', '', (string) $v);
            if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $v)) {
                $v = str_replace('.', '', $v);
            }

            return (float) str_replace(',', '.', $v);
        };

        $saved = 0;
        $spk = 0;
        $errors = [];
        $line = 0;
        DB::transaction(function () use ($handle, $delimiter, $byKode, $byNama, $toNumber, $berlakuMulai, &$saved, &$spk, &$errors, &$line) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                $key = mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''))));
                if ($key === '' || ($line === 1 && in_array($key, ['nama', 'kode', 'tindakan', 'nama tindakan'], true))) {
                    continue;
                }
                $kode = $byKode->get($key) ?? $byNama->get($key);
                if (!$kode) {
                    $errors[] = "Baris {$line}: tindakan '" . trim($row[0]) . "' tidak ditemukan";
                    continue;
                }
                $version = $this->service->setTarif(
                    $kode->id, $toNumber($row[1] ?? 0), $toNumber($row[2] ?? 0),
                    isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null,
                    $berlakuMulai, 'import', auth()->id()
                );
                if ($version) {
                    $saved++;
                    $spk += $version->spk_diperbarui;
                }
            }
        });
        fclose($handle);

        return response()->json(['success' => true, 'saved' => $saved, 'spk_diperbarui' => $spk, 'errors' => $errors]);
    }

    // ------------------------------------------------------------------ rekap

    public function rekap()
    {
        return view('erm.jasa_medis.rekap', ['kliniks' => Klinik::orderBy('nama')->get(['id', 'nama'])]);
    }

    public function rekapData(Request $request)
    {
        [$from, $to, $klinikId, $tipe] = $this->rekapFilters($request);
        $rows = $this->service->rekapRows($from, $to, $klinikId, $tipe);
        $tertunda = $rows->where('eligible', false);

        return response()->json([
            'periode' => Carbon::parse($from)->translatedFormat('d M Y') . ' – ' . Carbon::parse($to)->translatedFormat('d M Y'),
            'ringkasan' => JasaMedisService::ringkasan($rows),
            'total' => round($rows->where('eligible', true)->sum('nominal'), 2),
            'tertunda' => [
                'jumlah' => $tertunda->count(),
                'total' => round($tertunda->sum('nominal'), 2),
                'per_status' => $tertunda->groupBy('invoice')->map->count(),
            ],
            'tanpa_petugas' => $this->tanpaPetugas($from, $to, $klinikId),
            'rows' => $rows->values(),
        ]);
    }

    public function rekapExport(Request $request)
    {
        [$from, $to, $klinikId, $tipe] = $this->rekapFilters($request);
        $rows = $this->service->rekapRows($from, $to, $klinikId, $tipe);

        return Excel::download(new JasaMedisRekapExport($rows, $from, $to), "rekap-jasa-medis-{$from}-sd-{$to}.xlsx");
    }

    private function rekapFilters(Request $request): array
    {
        $bulan = $request->query('bulan');
        if ($bulan && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $start = Carbon::createFromFormat('Y-m-d', $bulan . '-01');
            $from = $start->toDateString();
            $to = $start->endOfMonth()->toDateString();
        } else {
            $from = $this->date($request->query('from')) ?? now()->startOfMonth()->toDateString();
            $to = $this->date($request->query('to')) ?? now()->endOfMonth()->toDateString();
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $tipe = in_array($request->query('tipe'), JasaMedisService::TIPES, true) ? $request->query('tipe') : null;

        return [$from, $to, $request->filled('klinik_id') ? (int) $request->query('klinik_id') : null, $tipe];
    }

    /** Riwayat tindakan of the period whose tindakan do not all have petugas yet: their jasa medis is missing */
    private function tanpaPetugas(string $from, string $to, ?int $klinikId): int
    {
        $isiPaket = DB::table('erm_tindakan_kode_tindakan')->selectRaw('COUNT(*)')->whereColumn('tindakan_id', 'r.tindakan_id');
        $terisi = JasaMedisService::terisiSubquery();

        return DB::table('erm_riwayat_tindakan as r')
            ->join('erm_visitations as v', 'v.id', '=', 'r.visitation_id')
            ->leftJoin('erm_spk as s', 's.riwayat_tindakan_id', '=', 'r.id')
            ->whereRaw('DATE(COALESCE(s.tanggal_tindakan, v.tanggal_visitation)) BETWEEN ? AND ?', [$from, $to])
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId))
            ->whereRaw('(' . $terisi->toSql() . ') < (' . $isiPaket->toSql() . ')')
            ->count();
    }

    private function date($value): ?string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
