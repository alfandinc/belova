<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use App\Models\ERM\SuratIstirahat;
use App\Models\ERM\SuratMondok;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPenunjang;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\Dokter;
use Carbon\Carbon; // Ensure Carbon is imported

class SuratIstirahatController extends Controller
{
    public function index($pasien_id)
    {
        $visitation = Visitation::where('pasien_id', $pasien_id)->latest()->first();
        $visitId = $visitation->id;

        $pasien = Pasien::with(['suratIstirahats', 'suratMondoks'])->findOrFail($pasien_id);
        $surats = $pasien->suratIstirahats()->with('dokter.user', 'dokter.spesialisasi')->get();

        $pasienData = PasienHelperController::getDataPasien($visitId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);

        $dokters = Dokter::with('user')->get();
        $dokterUserId = 1; // Default to first user for now

        // Get asesmen penunjang data for autofill
        $asesmenPenunjang = AsesmenPenunjang::where('visitation_id', $visitId)->first();

        // Check if the request is for DataTable AJAX data
        if (request()->ajax()) {
            return datatables()->of($surats)
                ->addColumn('dokter_name', function ($surat) {
                    return $surat->dokter->user->name ?? '-' . ' (' . $surat->dokter->spesialisasi->nama ?? '' . ')';
                })
                ->addColumn('spesialisasi', function ($surat) {
                    return $surat->dokter->spesialisasi->nama ?? '-';
                })
                ->addColumn('periode', function ($surat) {
                    $tanggalMulai = \Carbon\Carbon::parse($surat->tanggal_mulai)->format('d/m/Y');
                    $tanggalSelesai = \Carbon\Carbon::parse($surat->tanggal_selesai)->format('d/m/Y');
                    return $tanggalMulai . ' - ' . $tanggalSelesai;
                })
                ->addColumn('aksi', function ($surat) {
                    return '<a href="' . route('surat.istirahat', $surat->id) . '" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fas fa-print"></i> Cetak
                            </a>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('erm.suratistirahat.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'surats' => $surats,
            'dokters' => $dokters,
            'dokterUserId' => $dokterUserId,
            'asesmenPenunjang' => $asesmenPenunjang,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $jumlah_hari = now()->parse($request->tanggal_mulai)->diffInDays(now()->parse($request->tanggal_selesai)) + 1;

        $surat = SuratIstirahat::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlah_hari,
        ]);


        return response()->json($surat->load('pasien', 'dokter.user', 'dokter.spesialisasi'));
    }

    public function suratIstirahat($id)
    {
        try {
            $suratIstirahat = SuratIstirahat::with(['dokter.user', 'pasien'])->findOrFail($id);

            $dob = Carbon::parse($suratIstirahat->pasien->tanggal_lahir);
            $now = Carbon::now();
            $difference = $dob->diff($now);
            $umur = sprintf(
                '%d Tahun %d Bulan %d Hari',
                $difference->y, // Years
                $difference->m, // Months
                $difference->d  // Days
            );

            // Handle TTD (signature) image path
            $ttdPath = null;
            if ($suratIstirahat->dokter->ttd) {
                // Get the raw TTD path from database
                $rawTtdPath = $suratIstirahat->dokter->ttd;
                
                // Remove any leading slashes and normalize path
                $ttdRelativePath = ltrim($rawTtdPath, '/\\');
                
                // Ensure the path starts with img/qr/ if it doesn't already
                if (!str_starts_with($ttdRelativePath, 'img/qr/')) {
                    $ttdRelativePath = 'img/qr/' . basename($ttdRelativePath);
                }
                
                $ttdFullPath = public_path($ttdRelativePath);
                
                if (file_exists($ttdFullPath) && is_readable($ttdFullPath)) {
                    $ttdPath = $ttdRelativePath;
                }
            }

            $data = [
                'nama' => $suratIstirahat->pasien->nama ?? '-',
                'pekerjaan' => $suratIstirahat->pasien->pekerjaan ?? '-',
                'alamat' => $suratIstirahat->pasien->alamat ?? '-',
                'nama_dokter' => $suratIstirahat->dokter->user->name ?? '-',
                'ttd' => $ttdPath, // Now fully dynamic
                'tanggal_surat' => Carbon::now()->translatedFormat('d F Y'),
                'umur' => $umur,
                'tanggal_mulai' => $suratIstirahat->tanggal_mulai,
                'tanggal_selesai' => $suratIstirahat->tanggal_selesai,
                'jumlah_hari' => $suratIstirahat->jumlah_hari,
            ];

            // Create PDF with error handling for image loading
            $pdf = PDF::loadView('erm.suratistirahat.print-simple', $data)
                ->setPaper('a5', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'Arial',
                    'debugKeepTemp' => false,
                    'isRemoteEnabled' => false // Disable remote content loading
                ]);

            return $pdf->stream('surat-istirahat.pdf');
            
        } catch (\Exception $e) {
            // Return a simple error response
            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat PDF. Silakan coba lagi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAsesmenData($visitation_id)
    {
        $asesmen = AsesmenPenunjang::where('visitation_id', $visitation_id)->first();
        
        if (!$asesmen) {
            return response()->json([
                'diagnosa' => '',
                'instruksi_terapi' => ''
            ]);
        }

        // Combine all diagnosa kerja into one string
        $diagnosaArray = array_filter([
            $asesmen->diagnosakerja_1,
            $asesmen->diagnosakerja_2,
            $asesmen->diagnosakerja_3,
            $asesmen->diagnosakerja_4,
            $asesmen->diagnosakerja_5,
        ]);

        $diagnosa = implode(', ', $diagnosaArray);

        return response()->json([
            'diagnosa' => $diagnosa,
            'instruksi_terapi' => $asesmen->standing_order ?? ''
        ]);
    }

    public function storeMondok(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required',
            'tujuan_igd' => 'required|string|max:255',
            'diagnosa' => 'required|string',
            'instruksi_terapi' => 'required|string',
        ]);

        $surat = SuratMondok::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tujuan_igd' => $request->tujuan_igd,
            'diagnosa' => $request->diagnosa,
            'instruksi_terapi' => $request->instruksi_terapi,
        ]);

        return response()->json($surat->load('pasien', 'dokter.user', 'dokter.spesialisasi'));
    }

    public function getMondokData($pasien_id)
    {
        $pasien = Pasien::with('suratMondoks')->findOrFail($pasien_id);
        $suratsMondok = $pasien->suratMondoks()->with('dokter.user', 'dokter.spesialisasi')->get();

        return datatables()->of($suratsMondok)
            ->addColumn('dokter_name', function ($surat) {
                return $surat->dokter->user->name ?? '-' . ' (' . $surat->dokter->spesialisasi->nama ?? '' . ')';
            })
            ->addColumn('spesialisasi', function ($surat) {
                return $surat->dokter->spesialisasi->nama ?? '-';
            })
            ->addColumn('tujuan_igd_short', function ($surat) {
                return \Illuminate\Support\Str::limit($surat->tujuan_igd, 30);
            })
            ->addColumn('tanggal_dibuat', function ($surat) {
                return \Carbon\Carbon::parse($surat->created_at)->format('d/m/Y');
            })
            ->addColumn('aksi', function ($surat) {
                return '<a href="' . route('surat.mondok', $surat->id) . '" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="fas fa-print"></i> Cetak
                        </a>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
}
