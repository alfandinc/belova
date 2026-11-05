<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\ERM\RiwayatTindakan;
use App\Models\ERM\Visitation;
use App\Services\ERM\JasaMedisService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SPK Tindakan, filled at the end of each shift: for each tindakan in a riwayat's paket, who did it
 * (dokter / employee). The petugas drive the jasa medis rekap (JasaMedisController).
 */
class SpkController extends Controller
{
    private const MAX_RANGE_DAYS = 62;

    public function __construct(private JasaMedisService $service)
    {
    }

    public function index()
    {
        return view('erm.spk.index', [
            'kliniks' => Klinik::orderBy('nama')->get(['id', 'nama']),
            'dokters' => JasaMedisService::dokterOptions(),
            'employees' => JasaMedisService::employeeOptions(),
        ]);
    }


    /** Visits (one invoice each) of the dates in range with their paket tindakan and petugas progress */
    public function data(Request $request)
    {
        $from = $this->date($request->query('from')) ?? now()->toDateString();
        $to = $this->date($request->query('to')) ?? $from;
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > self::MAX_RANGE_DAYS) {
            return response()->json(['message' => 'Rentang tanggal maksimal ' . self::MAX_RANGE_DAYS . ' hari.'], 422);
        }

        $isiPaket = DB::table('erm_tindakan_kode_tindakan')->selectRaw('COUNT(*)')->whereColumn('tindakan_id', 'r.tindakan_id');
        $terisi = JasaMedisService::terisiSubquery();

        $riwayats = DB::table('erm_riwayat_tindakan as r')
            ->join('erm_visitations as v', 'v.id', '=', 'r.visitation_id')
            ->leftJoin('erm_pasiens as ps', 'ps.id', '=', 'v.pasien_id')
            ->leftJoin('erm_tindakan as t', 't.id', '=', 'r.tindakan_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as du', 'du.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as kl', 'kl.id', '=', 'v.klinik_id')
            ->whereRaw('DATE(v.tanggal_visitation) BETWEEN ? AND ?', [$from, $to])
            ->when($request->filled('klinik_id'), fn ($q) => $q->where('v.klinik_id', (int) $request->query('klinik_id')))
            ->select('r.id', 'r.visitation_id', 'v.pasien_id', 'ps.nama as pasien_nama', 't.nama as paket', 'du.name as dokter', 'kl.nama as klinik', 'kl.logo as klinik_logo')
            ->selectRaw('DATE(v.tanggal_visitation) as tanggal')
            ->selectSub($isiPaket, 'isi_paket')
            ->selectSub($terisi, 'terisi')
            ->orderBy('r.id')
            ->get();

        $invoices = JasaMedisService::visitInvoices($riwayats->pluck('visitation_id')->unique()->all());

        $rows = $riwayats->groupBy('visitation_id')->map(function ($group, $visitId) use ($invoices) {
            $first = $group->first();
            $invoice = $invoices[$visitId] ?? null;
            // A paket without tindakan has nothing to fill, so it does not count
            $isi = $group->sum(fn ($r) => (int) $r->isi_paket);
            $terisi = $group->sum(fn ($r) => min((int) $r->terisi, (int) $r->isi_paket));

            return [
                'visitation_id' => (string) $visitId,
                'tanggal' => $first->tanggal,
                'pasien_id' => $first->pasien_id,
                'pasien_nama' => $first->pasien_nama,
                'dokter' => $first->dokter,
                'klinik' => $first->klinik,
                'klinik_logo_url' => Klinik::logoUrl($first->klinik_logo),
                'paket' => $group->pluck('paket')->map(fn ($p) => $p ?? '-')->values(),
                'isi' => $isi,
                'terisi' => $terisi,
                'petugas_lengkap' => $terisi >= $isi,
                'invoice_number' => $invoice->invoice_number ?? null,
                'invoice_status' => $invoice->status ?? null,
                'invoice' => JasaMedisService::invoiceLabel($invoice->status ?? null),
            ];
        })->sortBy([['tanggal', 'desc'], ['visitation_id', 'desc']])->values();

        $counts = [
            'semua' => $rows->count(),
            'belum' => $rows->where('petugas_lengkap', false)->count(),
            'lengkap' => $rows->where('petugas_lengkap', true)->count(),
        ];

        $petugas = $request->query('petugas');
        if (in_array($petugas, ['belum', 'lengkap'], true)) {
            $rows = $rows->where('petugas_lengkap', $petugas === 'lengkap');
        }

        return response()->json(['data' => $rows->values(), 'counts' => $counts]);
    }

    public function show(Visitation $visitation)
    {
        return response()->json($this->service->visitFormData($visitation));
    }

    /** Save the SPK of every paket tindakan of the visit at once */
    public function save(Request $request, Visitation $visitation)
    {
        $data = $request->validate([
            'tanggal_tindakan' => 'required|date',
            'petugas' => 'required|array',
            'petugas.*' => 'array',
            'petugas.*.*.dokter' => 'nullable|array',
            'petugas.*.*.dokter.*' => 'integer',
            'petugas.*.*.employee' => 'nullable|array',
            'petugas.*.*.employee.*' => 'integer',
        ]);

        $riwayats = RiwayatTindakan::with('tindakan')->where('visitation_id', $visitation->id)->get();

        DB::transaction(function () use ($riwayats, $data) {
            foreach ($riwayats as $riwayat) {
                $this->service->save($riwayat, $data, $data['petugas'][$riwayat->id] ?? [], auth()->id());
            }
        });

        return response()->json(['success' => true, 'message' => 'SPK berhasil disimpan.']);
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
