<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use App\Models\ERM\SuratIstirahat;
use App\Models\ERM\Visitation;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\Dokter;
use Carbon\Carbon; // Ensure Carbon is imported

class SuratIstirahatController extends Controller
{
    public function index($pasien_id)
    {
        $visitation = Visitation::where('pasien_id', $pasien_id)->latest()->first();
        $visitId = $visitation->id;


        $pasien = Pasien::with('suratIstirahats')->findOrFail($pasien_id);
        $surats = $pasien->suratIstirahats()->with('dokter.user', 'dokter.spesialisasi')->get();

        $pasienData = PasienHelperController::getDataPasien($visitId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);


        $dokters = Dokter::with('user')->get();
        $dokterUserId = 1; // Default to first user for now

        // Check if the request is for DataTable AJAX data
        if (request()->ajax()) {
            return datatables()->of($surats)
                ->addColumn('dokter_name', function ($surat) {
                    return $surat->dokter->user->name ?? '-' . ' (' . $surat->dokter->spesialisasi->nama ?? '' . ')';
                })
                ->addColumn('spesialisasi', function ($surat) {
                    return $surat->dokter->spesialisasi->nama ?? '-';
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
}
