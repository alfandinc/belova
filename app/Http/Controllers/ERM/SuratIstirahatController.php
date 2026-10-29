<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use App\Models\ERM\SuratDiagnosa;
use App\Models\ERM\SuratIstirahat;
use App\Models\ERM\SuratMondok;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPenunjang;
use App\Models\ERM\Dokter;
use App\Support\PdfLetterhead;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Surat Keterangan page of a patient: Surat Istirahat, Surat Mondok and Surat Diagnosa (the last one is stored and
 * printed by RiwayatKunjunganController). Surat Istirahat / Mondok are printed here as A5 PDFs with the letterhead
 * of the visit's klinik.
 */
class SuratIstirahatController extends Controller
{
    /** Longest sick leave a Surat Istirahat may give, in days */
    public const MAX_HARI_ISTIRAHAT = 6;

    public function index(Request $request, $pasien_id)
    {
        // The Surat Istirahat table's own request needs none of the page data
        if ($request->ajax()) {
            return $this->istirahatData($pasien_id);
        }

        $pasien = Pasien::findOrFail($pasien_id);
        // The visit the page was opened from (navbar menu + identity card); the latest one when none is given
        $visitation = $request->filled('visitation_id')
            ? Visitation::where('pasien_id', $pasien_id)->whereKey($request->query('visitation_id'))->first()
            : null;
        $visitation ??= Visitation::where('pasien_id', $pasien_id)->latest('tanggal_visitation')->first();
        abort_if(!$visitation, 404, 'Pasien ini belum punya kunjungan.');

        $pasienData = PasienHelperController::getDataPasien($visitation->id);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitation->id);

        $dokters = Dokter::with('user:id,name')->get(['id', 'user_id'])
            ->sortBy(fn ($d) => mb_strtolower($d->user->name ?? ''))->values();
        // The visit's doctor, else the logged-in doctor
        $defaultDokterId = $visitation->dokter_id ?: Dokter::where('user_id', Auth::id())->value('id');

        return view('erm.suratistirahat.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'dokters' => $dokters,
            'defaultDokterId' => $defaultDokterId,
            'visitOptions' => $this->visitOptions($pasien_id),
        ], $pasienData, $createKunjunganData));
    }

    /**
     * The patient's visits for the Mondok / Diagnosa selects: date + doctor, its diagnosa kerja and the keterangan
     * of its Surat Diagnosa (a visit has at most one, saving again replaces it).
     */
    private function visitOptions($pasienId): array
    {
        $visits = Visitation::where('pasien_id', $pasienId)
            ->with(['dokter.user:id,name', 'asesmenPenunjang'])
            ->orderByDesc('tanggal_visitation')->orderByDesc('id')
            ->get(['id', 'pasien_id', 'dokter_id', 'tanggal_visitation']);
        $keterangan = SuratDiagnosa::whereIn('visitation_id', $visits->pluck('id'))->pluck('keterangan', 'visitation_id');

        return $visits->map(function ($visit) use ($keterangan) {
            $tanggal = $visit->tanggal_visitation ? Carbon::parse($visit->tanggal_visitation) : null;

            return [
                'id' => $visit->id,
                'label' => ($tanggal ? $tanggal->format('d/m/Y') : '-') . ' — ' . ($visit->dokter->user->name ?? 'Tanpa dokter'),
                'tanggal' => $tanggal ? $tanggal->format('Y-m-d') : null,
                'dokter_id' => $visit->dokter_id,
                'diagnosa' => self::diagnosaKerja($visit->asesmenPenunjang),
                'has_surat_diagnosa' => $keterangan->has($visit->id),
                'keterangan' => $keterangan->get($visit->id),
            ];
        })->all();
    }

    /** Diagnosa kerja 1-5 of an asesmen penunjang, without empty / filler values ('-', '0', '1') */
    private static function diagnosaKerja(?AsesmenPenunjang $asesmen): array
    {
        if (!$asesmen) {
            return [];
        }

        return collect(range(1, 5))
            ->map(fn ($i) => trim((string) $asesmen->{'diagnosakerja_' . $i}))
            ->reject(fn ($d) => $d === '' || in_array(strtolower($d), ['-', '0', '1'], true))
            ->values()->all();
    }

    /** Admin, or the doctor the surat is for, may delete it */
    private function canDelete($surat): bool
    {
        $user = Auth::user();

        return $user && ($user->hasRole('Admin') || optional($surat->dokter)->user_id === $user->id);
    }

    /** Doctor name with the spesialisasi under it, for the tables */
    private function dokterCell($surat): string
    {
        $nama = e($surat->dokter->user->name ?? '-');
        $spesialisasi = $surat->dokter->spesialisasi->nama ?? null;

        return $nama . ($spesialisasi ? '<div class="small text-muted">' . e($spesialisasi) . '</div>' : '');
    }

    /** Visible Cetak (prints the PDF straight away) and, when allowed, Hapus buttons */
    private function aksiButtons(string $printUrl, string $title, string $deleteUrl, bool $canDelete): string
    {
        $html = '<a href="' . e($printUrl) . '" class="btn btn-sm btn-outline-primary btn-pdf-print mr-1" data-title="' . e($title) . '"><i class="fas fa-print mr-1"></i>Cetak</a>';
        if ($canDelete) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus-surat" data-url="' . e($deleteUrl) . '" data-title="' . e($title) . '"><i class="fas fa-trash-alt mr-1"></i>Hapus</button>';
        }

        return '<div class="text-nowrap">' . $html . '</div>';
    }

    private function istirahatData($pasienId)
    {
        $surats = SuratIstirahat::where('pasien_id', $pasienId)
            ->with('dokter.user:id,name', 'dokter.spesialisasi:id,nama')
            ->orderByDesc('created_at')->orderByDesc('id')->get();

        return datatables()->of($surats)
            ->addColumn('dokter_name', fn ($surat) => $this->dokterCell($surat))
            ->addColumn('periode', function ($surat) {
                return Carbon::parse($surat->tanggal_mulai)->format('d/m/Y') . ' – ' . Carbon::parse($surat->tanggal_selesai)->format('d/m/Y')
                    . '<div class="small text-muted">' . (int) $surat->jumlah_hari . ' hari</div>';
            })
            ->addColumn('tanggal_dibuat', fn ($surat) => $surat->created_at ? $surat->created_at->format('d/m/Y') : '-')
            ->addColumn('aksi', fn ($surat) => $this->aksiButtons(
                route('surat.istirahat', $surat->id), 'Surat Istirahat',
                route('erm.suratistirahat.destroy', $surat->id), $this->canDelete($surat)
            ))
            ->rawColumns(['dokter_name', 'periode', 'aksi'])
            ->make(true);
    }

    /**
     * Tell Perawat, Pendaftaran and Farmasi about a new surat. Never fails the save.
     * Spesialisasi follows the klinik of the visit, or the dokter's own klinik (surat istirahat has no visit).
     */
    private function notifySurat($surat, string $title, array $detailLines, string $printUrl, $klinikId = null): void
    {
        try {
            $notif = \App\Support\ErmStaffNotifications::class;
            $surat->load(['pasien:id,nama', 'dokter.user:id,name', 'dokter.spesialisasi:id,nama']);
            $sender = optional(Auth::user())->name;

            $message = implode("\n", array_merge([
                'Pasien: ' . $notif::pasienLabel($surat->pasien),
                'Dokter: ' . $notif::dokterLabel($surat->dokter, $klinikId ?: optional($surat->dokter)->klinik_id, $sender),
            ], $detailLines));

            $notif::send(new \App\Notifications\SuratNotification($title, $message, $sender, $printUrl));
        } catch (\Throwable $e) {
            Log::error('Surat notification failed: ' . $e->getMessage(), ['surat_id' => $surat->id ?? null]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|exists:erm_dokters,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ], [
            'dokter_id.required' => 'Pilih dokter.',
            'dokter_id.exists' => 'Dokter tidak ditemukan.',
            'tanggal_mulai.required' => 'Tanggal mulai harus diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai harus diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $jumlahHari = Carbon::parse($request->tanggal_mulai)->startOfDay()->diffInDays(Carbon::parse($request->tanggal_selesai)->startOfDay()) + 1;
        if ($jumlahHari > self::MAX_HARI_ISTIRAHAT) {
            return response()->json([
                'message' => 'Maksimal istirahat ' . self::MAX_HARI_ISTIRAHAT . ' hari.',
                'errors' => ['tanggal_selesai' => ['Maksimal istirahat ' . self::MAX_HARI_ISTIRAHAT . ' hari.']],
            ], 422);
        }

        $surat = SuratIstirahat::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlahHari,
        ]);

        $notif = \App\Support\ErmStaffNotifications::class;
        $this->notifySurat($surat, 'Surat Istirahat Baru', [
            'Istirahat: ' . $jumlahHari . ' hari (' . $notif::tanggal($surat->tanggal_mulai) . ' – ' . $notif::tanggal($surat->tanggal_selesai) . ')',
        ], route('surat.istirahat', $surat->id));

        return response()->json([
            'success' => true,
            'message' => 'Surat istirahat ' . $jumlahHari . ' hari dibuat.',
            'id' => $surat->id,
            'print_url' => route('surat.istirahat', $surat->id),
        ]);
    }

    public function destroy($id)
    {
        $surat = SuratIstirahat::with('dokter')->findOrFail($id);
        abort_unless($this->canDelete($surat), 403, 'Hanya Admin atau dokter surat ini yang dapat menghapusnya.');
        $surat->delete();

        return response()->json(['success' => true, 'message' => 'Surat istirahat dihapus.']);
    }

    public function suratIstirahat($id)
    {
        $surat = SuratIstirahat::with(['dokter.user', 'dokter.spesialisasi', 'pasien'])->findOrFail($id);
        $tanggal = Carbon::parse($surat->created_at ?? now());

        return $this->streamSurat('erm.suratistirahat.print-simple', array_merge(
            $this->suratCommon($surat->pasien, $surat->dokter, $tanggal, $this->visitForSurat($surat->pasien_id, $tanggal, null, $surat->dokter_id)),
            [
                'tanggal_mulai' => $surat->tanggal_mulai,
                'tanggal_selesai' => $surat->tanggal_selesai,
                'jumlah_hari' => $surat->jumlah_hari,
            ]
        ), 'SuratIstirahat', $surat->pasien_id, $tanggal);
    }

    public function suratMondok($id)
    {
        $surat = SuratMondok::with(['dokter.user', 'dokter.spesialisasi', 'pasien'])->findOrFail($id);
        $tanggal = Carbon::parse($surat->created_at ?? now());

        return $this->streamSurat('erm.suratmondok.print-simple', array_merge(
            $this->suratCommon($surat->pasien, $surat->dokter, $tanggal, $this->visitForSurat($surat->pasien_id, $tanggal, $surat->visitation_id)),
            [
                'tujuan_igd' => $surat->tujuan_igd,
                'diagnosa' => $surat->diagnosa,
                'instruksi_terapi' => $surat->instruksi_terapi,
            ]
        ), 'SuratMondok', $surat->pasien_id, $tanggal);
    }

    /**
     * The visit a surat belongs to, for its klinik letterhead: the stored one (Surat Mondok), else the patient's
     * visit on the surat's date (with the surat's dokter first, so two klinik on one day pick the right one),
     * else the last one before it, else the latest. Surat Istirahat has no visit column.
     */
    private function visitForSurat($pasienId, Carbon $tanggal, $visitationId = null, $dokterId = null): ?Visitation
    {
        if ($visitationId && ($visit = Visitation::with('klinik')->find($visitationId))) {
            return $visit;
        }
        $visits = Visitation::with('klinik')->where('pasien_id', $pasienId);
        $sameDay = (clone $visits)->whereDate('tanggal_visitation', $tanggal->toDateString());

        return ($dokterId ? (clone $sameDay)->where('dokter_id', $dokterId)->latest('id')->first() : null)
            ?? $sameDay->latest('id')->first()
            ?? (clone $visits)->whereDate('tanggal_visitation', '<', $tanggal->toDateString())->latest('tanggal_visitation')->first()
            ?? (clone $visits)->latest('tanggal_visitation')->first();
    }

    /** Fields shared by Surat Istirahat and Surat Mondok; the patient's age is the age on the surat's date */
    private function suratCommon(?Pasien $pasien, ?Dokter $dokter, Carbon $tanggal, ?Visitation $visit): array
    {
        abort_if(!$pasien, 404, 'Data pasien surat ini tidak ditemukan.');

        $umur = '-';
        if ($pasien->tanggal_lahir) {
            $diff = Carbon::parse($pasien->tanggal_lahir)->diff($tanggal);
            $umur = sprintf('%d Tahun %d Bulan %d Hari', $diff->y, $diff->m, $diff->d);
        }
        // Doctor's QR signature as a small JPEG (DomPDF needs seconds for the big PNG)
        $ttdFile = basename((string) ($dokter->ttd ?? ''));

        return [
            'kop' => PdfLetterhead::forVisitation($visit ?? new Visitation()),
            'nama' => $pasien->nama ?: '-',
            'umur' => $umur,
            'pekerjaan' => $pasien->pekerjaan ?: '-',
            'alamat' => $pasien->full_alamat ?: ($pasien->alamat ?: '-'),
            'nama_dokter' => $dokter->user->name ?? '-',
            'spesialisasi' => $dokter->spesialisasi->nama ?? null,
            'ttd' => $ttdFile !== '' ? PdfLetterhead::logo('img/qr/' . $ttdFile, 300) : null,
            'tanggal_surat' => $tanggal->copy()->locale('id')->translatedFormat('j F Y'),
        ];
    }

    private function streamSurat(string $view, array $data, string $what, $pasienId, Carbon $tanggal)
    {
        $fileName = $what . '_' . preg_replace('/[^A-Za-z0-9-]/', '', (string) $pasienId) . '_' . $tanggal->format('Ymd') . '.pdf';
        try {
            return Pdf::loadView($view, $data)->setPaper('a4', 'portrait')->stream($fileName);
        } catch (\Throwable $e) {
            Log::error($what . ' PDF gagal dibuat: ' . $e->getMessage());

            return response()->json(['message' => 'PDF gagal dibuat. Silakan coba lagi atau hubungi admin.'], 500);
        }
    }

    public function getAsesmenData($visitation_id)
    {
        $asesmen = AsesmenPenunjang::where('visitation_id', $visitation_id)->first();

        return response()->json([
            'diagnosa' => implode(', ', self::diagnosaKerja($asesmen)),
            'instruksi_terapi' => $asesmen->standing_order ?? '',
        ]);
    }

    public function storeMondok(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|exists:erm_dokters,id',
            'tujuan_igd' => 'required|string|max:255',
            'diagnosa' => 'required|string',
            'instruksi_terapi' => 'required|string',
            'created_at' => 'nullable|date',
        ], [
            'dokter_id.required' => 'Pilih dokter.',
            'dokter_id.exists' => 'Dokter tidak ditemukan.',
            'tujuan_igd.required' => 'Tujuan IGD harus diisi.',
            'diagnosa.required' => 'Diagnosa harus diisi.',
            'instruksi_terapi.required' => 'Instruksi terapi harus diisi.',
        ]);

        $visitation = Visitation::where('id', $request->visitation_id)
            ->where('pasien_id', $request->pasien_id)
            ->first();

        if (! $visitation) {
            return response()->json([
                'message' => 'Kunjungan tidak sesuai dengan pasien.',
                'errors' => ['visitation_id' => ['Kunjungan tidak sesuai dengan pasien.']],
            ], 422);
        }

        if ($request->filled('created_at')) {
            $suratDate = Carbon::parse($request->created_at);
        } elseif ($visitation->tanggal_visitation) {
            $suratDate = Carbon::parse($visitation->tanggal_visitation);
        } else {
            $suratDate = now();
        }

        $surat = SuratMondok::create([
            'visitation_id' => $visitation->id,
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tujuan_igd' => $request->tujuan_igd,
            'diagnosa' => $request->diagnosa,
            'instruksi_terapi' => $request->instruksi_terapi,
            'created_at' => $suratDate,
            'updated_at' => $suratDate,
        ]);

        $this->notifySurat($surat, 'Surat Mondok Baru', [
            'Tujuan IGD: ' . $surat->tujuan_igd,
            'Diagnosa: ' . \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', strip_tags($surat->diagnosa)), 120),
            'Tanggal: ' . \App\Support\ErmStaffNotifications::tanggal($suratDate),
        ], route('surat.mondok', $surat->id), $visitation->klinik_id);

        return response()->json([
            'success' => true,
            'message' => 'Surat mondok dibuat.',
            'id' => $surat->id,
            'print_url' => route('surat.mondok', $surat->id),
        ]);
    }

    public function destroyMondok($id)
    {
        $surat = SuratMondok::with('dokter')->findOrFail($id);
        abort_unless($this->canDelete($surat), 403, 'Hanya Admin atau dokter surat ini yang dapat menghapusnya.');
        $surat->delete();

        return response()->json(['success' => true, 'message' => 'Surat mondok dihapus.']);
    }

    public function getMondokData($pasien_id)
    {
        $suratsMondok = SuratMondok::where('pasien_id', $pasien_id)
            ->with('dokter.user:id,name', 'dokter.spesialisasi:id,nama')
            ->orderByDesc('created_at')->orderByDesc('id')->get();

        return datatables()->of($suratsMondok)
            ->addColumn('dokter_name', fn ($surat) => $this->dokterCell($surat))
            ->addColumn('tujuan_igd_short', fn ($surat) => e(\Illuminate\Support\Str::limit($surat->tujuan_igd, 40)))
            ->addColumn('tanggal_dibuat', fn ($surat) => $surat->created_at ? $surat->created_at->format('d/m/Y') : '-')
            ->addColumn('aksi', fn ($surat) => $this->aksiButtons(
                route('surat.mondok', $surat->id), 'Surat Mondok',
                route('erm.suratmondok.destroy', $surat->id), $this->canDelete($surat)
            ))
            ->rawColumns(['dokter_name', 'aksi'])
            ->make(true);
    }
}
