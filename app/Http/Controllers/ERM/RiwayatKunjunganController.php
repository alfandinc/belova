<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Visitation;
use App\Models\ERM\SuratDiagnosa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatKunjunganController extends Controller
{
    public function printSuratDiagnosisEn($visitationId)
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
        $suratDiagnosa = SuratDiagnosa::where('visitation_id', $visitationId)->first();

        // Get diagnosis list
        $diagnosisList = [];
        if ($visitation->asesmenPenunjang) {
            $diagnosisList = array_filter([
                $visitation->asesmenPenunjang->diagnosakerja_1 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_2 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_3 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_4 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_5 ?? '',
            ]);
        }
        if (empty($diagnosisList) && $suratDiagnosa && !empty($suratDiagnosa->keterangan)) {
            $diagnosisList[] = $suratDiagnosa->keterangan;
        }

        $birthDate = \Carbon\Carbon::parse($pasien['pasien']->tanggal_lahir);
        $age = $birthDate->age;

        $data = [
            'visitation_id' => $visitation->id,
            'tanggal_visit' => $visitation->tanggal_visitation,
            'nama_dokter' => $visitation->dokter->user->name ?? 'Doctor',
            'spesialisasi' => $visitation->dokter->spesialisasi->nama ?? '-',
            'pasien' => $pasien['pasien'],
            'diagnosis_list' => $diagnosisList,
            'keterangan' => $suratDiagnosa->keterangan ?? '-',
            'ttd_image_path' => '',
            'umur' => $age
        ];

        try {
            $pdf = \PDF::loadView('erm.riwayatkunjungan.surat-diagnosis-en', $data);
            return $pdf->stream('medical-diagnosis-certificate.pdf');
        } catch (\Exception $e) {
            \Log::error('Error generating English surat diagnosis PDF: ' . $e->getMessage());
            return response()->view('errors.custom', [
                'message' => 'Error generating English diagnosis certificate. Please try again or contact admin.',
                'exception' => $e
            ], 500);
        }
    }
    public function getDataDiagnosisTable($pasienId)
    {
        $diagnosas = SuratDiagnosa::whereHas('visitation', function($q) use ($pasienId) {
            $q->where('pasien_id', $pasienId);
        })->with(['visitation.dokter.user', 'visitation.dokter.spesialisasi'])->get();

        return \Yajra\DataTables\DataTables::of($diagnosas)
            ->addIndexColumn()
            ->addColumn('dokter', function($row) {
                return $row->visitation->dokter->user->name ?? '-';
            })
            ->addColumn('spesialisasi', function($row) {
                return $row->visitation->dokter->spesialisasi->nama ?? '-';
            })
            ->addColumn('keterangan', function($row) {
                return $row->keterangan ?? '-';
            })
            ->addColumn('tanggal', function($row) {
                return $row->created_at ? $row->created_at->format('d/m/Y') : '-';
            })
            ->addColumn('aksi', function($row) {
                $printUrl = route('riwayatkunjungan.print-surat-diagnosis', $row->visitation_id);
                $printUrlEn = route('riwayatkunjungan.print-surat-diagnosis-en', $row->visitation_id);
                return '<a href="' . $printUrl . '" target="_blank" class="btn btn-sm btn-secondary mr-1"><i class="fas fa-print"></i> Cetak</a>' .
                       '<a href="' . $printUrlEn . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-print"></i> Print (EN)</a>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
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

            // Apply filters if present
            if ($request->filled('status_kunjungan')) {
                // allow numeric or string values
                $status = $request->get('status_kunjungan');
                $kunjungan->where('status_kunjungan', $status);
            }

            if ($request->filled('jenis_kunjungan')) {
                $jenis = $request->get('jenis_kunjungan');
                // In case jenis_kunjungan is stored in a column named 'jenis_kunjungan'
                // or 'jenis_kunjungan' uses specific codes, adjust mapping if needed.
                $kunjungan->where('jenis_kunjungan', $jenis);
            }

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
                    // Determine Dokumen URL based on status_dokumen
                    $dokumenUrl = route('resume.medis', $row->id);
                    try {
                        $statusDokumen = isset($row->status_dokumen) ? strtolower($row->status_dokumen) : null;
                        if ($statusDokumen === 'cppt') {
                            $dokumenUrl = route('erm.cppt.create', $row->id);
                        } elseif ($statusDokumen === 'asesmen') {
                            $dokumenUrl = route('erm.asesmendokter.create', ['visitation' => $row->id]);
                        } else {
                            // keep resume as fallback
                            $dokumenUrl = route('resume.medis', $row->id);
                        }
                    } catch (\Exception $e) {
                        // If building route fails, fallback to resume
                        Log::warning('Error building dokumen URL for visitation ' . $row->id . ': ' . $e->getMessage());
                        $dokumenUrl = route('resume.medis', $row->id);
                    }
                    $diagnosisBtn = '<button class="btn btn-sm btn-success diagnosis-btn" data-id="' . $row->id . '">Surat Diagnosis</button>';

                    // If jenis_kunjungan is Beli Produk (2) or Laboratorium (3) then only show the specific button(s)
                    $jenis = isset($row->jenis_kunjungan) ? (int)$row->jenis_kunjungan : null;

                    if ($jenis === 2) {
                        // Beli Produk -> show only Resep
                        try {
                            $resepUrl = route('erm.eresep.create', $row->id);
                            return '<a href="' . $resepUrl . '" class="btn btn-sm btn-success" target="_blank">Resep</a>';
                        } catch (\Exception $e) {
                            Log::warning('Route erm.eresep.create not found or error building URL: ' . $e->getMessage());
                            return '';
                        }
                    }

                    if ($jenis === 3) {
                        // Laboratorium -> show only Lab
                        try {
                            $elabUrl = route('erm.elab.create', $row->id);
                            return '<a href="' . $elabUrl . '" class="btn btn-sm btn-warning" target="_blank">Lab</a>';
                        } catch (\Exception $e) {
                            Log::warning('Route erm.elab.create not found or error building URL: ' . $e->getMessage());
                            return '';
                        }
                    }

                    // Default: show Resume, Dokumen and Diagnosis buttons
                    $buttons = '';
                    $buttons .= '<a href="' . $resumeUrl . '" class="btn btn-sm btn-primary" target="_blank">Resume</a> ';
                    $buttons .= '<a href="' . $dokumenUrl . '" class="btn btn-sm btn-secondary dokumen-btn" target="_blank">Dokumen</a> ';
                    $buttons .= $diagnosisBtn;

                    return $buttons;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }


        // Compute stats for this pasien
        // Total visits with status_kunjungan == 2 (assumed 'Sudah diperiksa')
        $totalVisits = Visitation::where('pasien_id', $pasien)
            ->where('status_kunjungan', 2)
            ->count();

        // Total spend from invoices linked to visitations of this pasien
        // Join finance_invoices where visitation.pasien_id == $pasien
        $totalSpend = \App\Models\Finance\Invoice::whereHas('visitation', function ($q) use ($pasien) {
            $q->where('pasien_id', $pasien);
        })->sum('total_amount');

        return view('erm.riwayatkunjungan.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'stats' => [
                'total_visits' => $totalVisits,
                'total_spend' => $totalSpend,
            ],
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

        // Handle TTD image path in a simple way
        $ttdFileName = $visitation->dokter->ttd ?? '';
        $ttdImagePath = '';
        
        // Create a simple and reliable image path for the PDF
        if (!empty($ttdFileName)) {
            // Always look in the img/qr directory
            $ttdFilePath = public_path('img/qr/' . $ttdFileName);
            
            if (file_exists($ttdFilePath)) {
                // If file exists, embed it as base64 image
                $ttdImagePath = 'data:image/png;base64,' . base64_encode(file_get_contents($ttdFilePath));
                Log::info('TTD file found and embedded: img/qr/' . $ttdFileName);
            } else {
                // If file doesn't exist, log it and leave image path empty
                Log::warning('TTD file not found: img/qr/' . $ttdFileName);
                // No image will be shown
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
            'ttd_image_path' => $ttdImagePath,
        ];

        try {
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
    
    public function getDataDiagnosis($visitationId)
    {
        $visitation = Visitation::with([
            'dokter.user', 
            'dokter.spesialisasi', 
            'asesmenPenunjang'
        ])->findOrFail($visitationId);
        
        $pasien = PasienHelperController::getDataPasien($visitationId);
        
        // Get diagnoses from asesmenPenunjang
        $diagnosisList = array();
        if ($visitation->asesmenPenunjang) {
            $diagnosisList = array_filter([
                $visitation->asesmenPenunjang->diagnosakerja_1 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_2 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_3 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_4 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_5 ?? '',
            ]);
        }
        
        // Get existing surat diagnosa if available
        $suratDiagnosa = SuratDiagnosa::where('visitation_id', $visitationId)->first();
        
        return response()->json([
            'visitation' => $visitation,
            'pasien' => $pasien['pasien'],
            'diagnoses' => $diagnosisList,
            'suratDiagnosa' => $suratDiagnosa
        ]);
    }
    
    public function storeSuratDiagnosis(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required',
            'keterangan' => 'nullable|string'
        ]);
        
        // Create or update the surat diagnosa
        SuratDiagnosa::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            ['keterangan' => $request->keterangan]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Surat diagnosa berhasil disimpan'
        ]);
    }
    
    public function printSuratDiagnosis($visitationId)
    {
        $visitation = Visitation::with([
            'dokter.user', 
            'dokter.spesialisasi', 
            'asesmenPenunjang'
        ])->findOrFail($visitationId);
        
        $pasien = PasienHelperController::getDataPasien($visitationId);
        $suratDiagnosa = SuratDiagnosa::where('visitation_id', $visitationId)->first();
        
        // Get diagnoses
        $diagnosisList = array();
        if ($visitation->asesmenPenunjang) {
            $diagnosisList = array_filter([
                $visitation->asesmenPenunjang->diagnosakerja_1 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_2 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_3 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_4 ?? '',
                $visitation->asesmenPenunjang->diagnosakerja_5 ?? '',
            ]);
        }
        
        // Handle TTD image path
        $ttdFileName = $visitation->dokter->ttd ?? '';
        $ttdImagePath = '';
        
        if (!empty($ttdFileName)) {
            $ttdFilePath = public_path('img/qr/' . $ttdFileName);
            
            if (file_exists($ttdFilePath)) {
                $ttdImagePath = 'data:image/png;base64,' . base64_encode(file_get_contents($ttdFilePath));
            }
        }
        
        // Get age from birthdate
        $birthDate = \Carbon\Carbon::parse($pasien['pasien']->tanggal_lahir);
        $age = $birthDate->age;
        
        $data = [
            'visitation_id' => $visitation->id,
            'tanggal_visit' => $visitation->tanggal_visitation,
            'nama_dokter' => $visitation->dokter->user->name ?? 'Dokter',
            'spesialisasi' => $visitation->dokter->spesialisasi->nama ?? '-',
            'pasien' => $pasien['pasien'],
            'diagnosis_list' => $diagnosisList,
            'keterangan' => $suratDiagnosa->keterangan ?? '-',
            'ttd_image_path' => $ttdImagePath,
            'umur' => $age
        ];
        
        try {
            $pdf = PDF::loadView('erm.riwayatkunjungan.surat-diagnosis', $data);
            return $pdf->stream('surat-diagnosis.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating surat diagnosis PDF: ' . $e->getMessage());
            return response()->view('errors.custom', [
                'message' => 'Terjadi kesalahan saat membuat surat diagnosis. Silakan coba lagi atau hubungi admin.',
                'exception' => $e
            ], 500);
        }
    }
}
