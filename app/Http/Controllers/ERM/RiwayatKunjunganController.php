<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatKunjunganController extends Controller
{
    public function index(Request $request, $pasien)
    {

        $visitation = Visitation::where('pasien_id', $pasien)->latest()->first();
        $visitId = $visitation->id;
        $pasienData = Cache::remember("pasien_data_{$visitId}", 60, function () use ($visitId) {
            return PasienHelperController::getDataPasien($visitId);
        });
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);

        // \Log::info('Masuk controller riwayatkunjungan');
        if ($request->ajax()) {
            $kunjungan = Visitation::with(['metodeBayar:id,nama', 'dokter.user:id,name', 'dokter.spesialisasi:id,nama'])
                ->select('erm_visitations.*') // ini penting!
                ->where('pasien_id', $pasien)
                ->orderBy('erm_visitations.created_at', 'desc');

            return DataTables::of($kunjungan)
                ->addIndexColumn()
                ->addColumn('metode', fn($row) => $row->metodeBayar->nama ?? '-')
                ->addColumn('spesialisasi', function ($row) {
                    return optional(optional($row->dokter)->spesialisasi)->nama ?? '-';
                })
                ->addColumn('dokter', function ($row) {
                    return optional(optional($row->dokter)->user)->name ?? '-';
                })
                ->editColumn('tanggal_visitation', function ($row) {
                    return \Carbon\Carbon::parse($row->tanggal_visitation)->translatedFormat('d F Y');
                })
                // ->editColumn('created_at', fn($row) => $row->created_at->translatedFormat('d F Y'))
                ->addColumn('aksi', function ($row) {
                    // $resumeUrl = route('resume.show', $row->id);   // Ubah sesuai kebutuhan
                    $resumeUrl = route('resume.medis', $row->id);
                    $dokumenUrl = route('erm.asesmendokter.create', ['visitation' => $row->id]);

                    return '
                        <a href="' . $resumeUrl . '" class="btn btn-sm btn-primary" target="_blank">Resume</a>
                         <a href="' . $dokumenUrl . '" class="btn btn-sm btn-secondary" target="_blank">Dokumen</a>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }



        return view('erm.riwayatkunjungan.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
        ], $pasienData, $createKunjunganData));
    }

    public function resumeMedis($visitationId)
    {
        $visitation = Visitation::with([
            'dokter.user', 
            'dokter.spesialisasi', 
            'asesmenDalam', 
            'asesmenAnak',
            'asesmenEstetika',
            'asesmenSaraf',
            'asesmenGigi',
            'asesmenPenunjang',
            'asesmenUmum',
            'asesmenPerawat',
            'resepDokter.obat'
        ])->findOrFail($visitationId);

        $pasien = PasienHelperController::getDataPasien($visitationId);
        
        // Get medication names
        $obatNames = $visitation->resepDokter()->with('obat')->get()->map(function ($resep) {
            return $resep->obat->nama ?? 'Unknown';
        })->toArray();

        // Determine which asesmen to use based on what's available
        $asesmen = null;
        $keluhanUtama = '-';
        $keadaanUmum = 'Baik';
        $n = '-';
        $td = '-';
        $r = '-';
        $s = '-';

        // Try to get data from any available asesmen in priority order
        if ($visitation->asesmenDalam) {
            $asesmen = $visitation->asesmenDalam;
        } elseif ($visitation->asesmenAnak) {
            $asesmen = $visitation->asesmenAnak;
        } elseif ($visitation->asesmenEstetika) {
            $asesmen = $visitation->asesmenEstetika;
        } elseif ($visitation->asesmenSaraf) {
            $asesmen = $visitation->asesmenSaraf;
        } elseif ($visitation->asesmenGigi) {
            $asesmen = $visitation->asesmenGigi;
        } elseif ($visitation->asesmenUmum) {
            $asesmen = $visitation->asesmenUmum;
        } elseif ($visitation->asesmenPerawat) {
            $asesmen = $visitation->asesmenPerawat;
        }
        
        // Extract common fields that should be present in all asesmen types
        if ($asesmen) {
            // Try to extract keluhan_utama with different possible field names
            $keluhanUtamaFields = ['keluhan_utama', 'keluhan', 'keluhan_pasien'];
            foreach ($keluhanUtamaFields as $field) {
                if (isset($asesmen->$field) && !empty($asesmen->$field)) {
                    $keluhanUtama = $asesmen->$field;
                    break;
                }
            }
            
            // Try to extract keadaan_umum with different possible field names
            $keadaanUmumFields = ['keadaan_umum', 'keadaan'];
            foreach ($keadaanUmumFields as $field) {
                if (isset($asesmen->$field) && !empty($asesmen->$field)) {
                    $keadaanUmum = $asesmen->$field;
                    break;
                }
            }
            
            // Extract vital signs
            $n = $asesmen->n ?? $asesmen->nadi ?? $asesmen->denyut_nadi ?? '-';
            $td = $asesmen->td ?? $asesmen->tekanan_darah ?? '-';
            $r = $asesmen->r ?? $asesmen->respirasi ?? $asesmen->pernapasan ?? '-';
            $s = $asesmen->s ?? $asesmen->suhu ?? '-';
        }
        
        // Get diagnosis list from any available source
        $diagnosisList = [];
        
        // Try from asesmenPenunjang first
        if ($visitation->asesmenPenunjang) {
            $diagnosisList = array_filter([
                $visitation->asesmenPenunjang->diagnosakerja_1 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_2 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_3 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_4 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_5 ?? '',
            ]);
        }
        
        // If no diagnoses found in asesmenPenunjang, try from the selected asesmen
        if (empty($diagnosisList) && $asesmen && isset($asesmen->diagnosa)) {
            $diagnosisList[] = $asesmen->diagnosa;
        }
        
        // If still empty, try other possible field names in the selected asesmen
        if (empty($diagnosisList) && $asesmen) {
            $possibleDiagnosisFields = ['diagnosis', 'diagnosa_kerja', 'assessment'];
            foreach ($possibleDiagnosisFields as $field) {
                if (isset($asesmen->$field) && !empty($asesmen->$field)) {
                    $diagnosisList[] = $asesmen->$field;
                    break;
                }
            }
        }

        $data = [
            'visitation_id' => $visitation->id,
            'tanggal_visit' => $visitation->tanggal_visitation,
            'nama_dokter' => $visitation->dokter->user->name ?? 'Dokter',
            'spesialisasi' => $visitation->dokter->spesialisasi->nama ?? '-',
            'pasien' => $pasien['pasien'],
            'keluhan_utama' => $keluhanUtama,
            'keadaan_umum' => $keadaanUmum,
            'n' => $n,
            'td' => $td,
            'r' => $r,
            's' => $s,
            'nama_obat' => !empty($obatNames) ? implode(', ', $obatNames) : '-',
            'diagnosis' => !empty($diagnosisList) ? implode(', ', $diagnosisList) : '-',
            'tindak_lanjut' => $visitation->asesmenPenunjang ? ($visitation->asesmenPenunjang->standing_order ?? '-') : '-',
            'ttd' => $visitation->dokter->ttd ?? '',
            // Store the full path for debugging
            'ttd_debug' => $visitation->dokter->ttd ? ('Original path: ' . $visitation->dokter->ttd) : 'No TTD found',
        ];

        // Process TTD path and check for file existence
        if (!empty($data['ttd'])) {
            $originalTtd = $data['ttd'];
            
            // Special case for the specific TTD we're having trouble with
            if ($originalTtd === 'qr-wahyuaji.png') {
                if (file_exists(public_path('img/qr/qr-wahyuaji.png'))) {
                    Log::info('Found the wahyuaji QR image in img/qr/');
                    $data['ttd'] = 'img/qr/qr-wahyuaji.png';
                }
            } else {
                // Try different path combinations
                $possiblePaths = [
                    $originalTtd,
                    'img/qr/' . $originalTtd,
                ];
                
                // If it starts with qr-, try also without the prefix
                if (strpos($originalTtd, 'qr-') === 0) {
                    $possiblePaths[] = 'img/qr/' . substr($originalTtd, 3);
                }
                
                // Try without file extension if it has one
                $pathInfo = pathinfo($originalTtd);
                if (isset($pathInfo['extension'])) {
                    $baseNameWithoutExt = $pathInfo['filename'];
                    $possiblePaths[] = 'img/qr/' . $baseNameWithoutExt . '.png';
                    $possiblePaths[] = 'img/qr/' . $baseNameWithoutExt . '.jpg';
                }
                
                $found = false;
                foreach ($possiblePaths as $path) {
                    if (file_exists(public_path($path))) {
                        $found = true;
                        Log::info('TTD file found at: ' . $path);
                        // Update the TTD path in the data array
                        $data['ttd'] = $path;
                        break;
                    }
                }
                
                if (!$found) {
                    Log::warning('TTD file not found: ' . $originalTtd . '. Tried paths: ' . implode(', ', $possiblePaths));
                }
            }
        }

        try {
            // Add the TTD image data directly if found
            if (!empty($data['ttd']) && file_exists(public_path($data['ttd']))) {
                try {
                    $data['ttd_image_data'] = base64_encode(file_get_contents(public_path($data['ttd'])));
                    $data['ttd_exists'] = true;
                } catch (\Exception $imgEx) {
                    Log::error('Error loading TTD image: ' . $imgEx->getMessage());
                    $data['ttd_exists'] = false;
                }
            } else {
                $data['ttd_exists'] = false;
            }
            
            $pdf = PDF::loadView('erm.riwayatkunjungan.resume-medis', $data);
            return $pdf->stream('resume-medis.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating resume PDF: ' . $e->getMessage());
            return response()->view('errors.custom', [
                'message' => 'Terjadi kesalahan saat membuat resume medis. Silakan coba lagi atau hubungi admin.',
                'exception' => $e
            ], 500);
        }
    }
}
