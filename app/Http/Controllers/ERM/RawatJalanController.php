<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\Klinik;
use App\Models\ERM\ScreeningBatuk;
use Illuminate\Support\Facades\Log;

class RawatJalanController extends Controller
{
    /**
     * Send notification from Dokter to all Perawat users
     */
    public function sendNotifToPerawat(Request $request)
    {
        if (!Auth::user()->hasRole('Dokter')) {
            return response()->json(['success' => false], 403);
        }
        $request->validate([
            'message' => 'required|string|max:255',
        ]);
        $perawats = \App\Models\User::role('Perawat')->get();
        \Log::info('Perawat IDs:', $perawats->pluck('id')->toArray());
        foreach ($perawats as $perawat) {
            \Log::info('Sending notification to Perawat ID: ' . $perawat->id);
            $perawat->notify(new \App\Notifications\DokterToPerawatNotification($request->message));
        }
        return response()->json(['success' => true]);
    }

    /**
     * Poll for unread notifications for Perawat
     */
    public function getNotif()
    {
        $user = Auth::user();
        if (!$user->hasRole('Perawat')) {
            return response()->json(['new' => false]);
        }
        $notif = $user->unreadNotifications()->latest()->first();
        if ($notif) {
            $notif->markAsRead();
            return response()->json(['new' => true, 'message' => $notif->data['message'], 'sender' => $notif->data['sender']]);
        }
        return response()->json(['new' => false]);
    }
        /**
     * Restore visitation status from dibatalkan (7) to tidak datang (0)
     */
    public function restoreStatus(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
        ]);
        $visitation = Visitation::findOrFail($request->visitation_id);
        if ($visitation->status_kunjungan == 7) {
            $visitation->status_kunjungan = 0;
            $visitation->save();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Status kunjungan bukan dibatalkan'], 400);
        }
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::query()
                ->select([
                    'erm_visitations.id',
                    'erm_visitations.pasien_id',
                    'erm_visitations.metode_bayar_id',
                    'erm_visitations.dokter_id',
                    'erm_visitations.klinik_id',
                    'erm_visitations.status_kunjungan',
                    'erm_visitations.status_dokumen',
                    'erm_visitations.jenis_kunjungan',
                    'erm_visitations.tanggal_visitation',
                    'erm_visitations.waktu_kunjungan',
                    'erm_visitations.no_antrian',
                    'erm_pasiens.nama as nama_pasien',
                    'erm_pasiens.id as no_rm',
                    'erm_pasiens.no_hp as telepon_pasien',
                    'erm_pasiens.gender as gender',
                    'erm_pasiens.tanggal_lahir as tanggal_lahir',
                    'erm_pasiens.status_pasien as status_pasien',
                    'erm_pasiens.status_akses as status_akses'
                ])
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
                ->where('erm_visitations.status_kunjungan', '!=', 7);

            if ($request->filled('klinik_id')) {
                $visitations->where('erm_visitations.klinik_id', $request->klinik_id);
            }
            $user = Auth::user();
            if ($user && $user->hasRole('Dokter')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $visitations->where('erm_visitations.dokter_id', $dokter->id);
                }
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $visitations->whereDate('erm_visitations.tanggal_visitation', '>=', $request->start_date)
                    ->whereDate('erm_visitations.tanggal_visitation', '<=', $request->end_date);
            }
            if ($request->filled('dokter_id')) {
                $visitations->where('erm_visitations.dokter_id', $request->dokter_id);
            }
            $visitations->with([
                'metodeBayar:id,nama',
                'dokter.user:id,name',
                'dokter.spesialisasi:id,nama',
                'screeningBatuk:id,visitation_id',
                'labPermintaan:id,visitation_id',
                'riwayatTindakan:id,visitation_id',
                'asesmenPenunjang:id,visitation_id,created_at',
                'cppt:id,visitation_id,created_at'
            ]);
            return datatables()->of($visitations)
                ->filterColumn('nama_pasien', function ($query, $keyword) {
                    $query->where('erm_pasiens.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('no_rm', function ($query, $keyword) {
                    $query->where('erm_pasiens.id', 'like', "%{$keyword}%");
                })
                ->addColumn('antrian', function ($v) {
                    $antrianHtml = '<span data-order="' . intval($v->no_antrian) . '">' . $v->no_antrian . '</span>';
                    if ($v->labPermintaan && $v->labPermintaan->count() > 0) {
                        $antrianHtml .= ' <i class="fas fa-flask blinking" title="Ada permintaan lab"></i>';
                    }
                    if ($v->riwayatTindakan && $v->riwayatTindakan->count() > 0) {
                        $antrianHtml .= ' <i class="fas fa-stethoscope blinking" title="Ada tindakan"></i>';
                    }
                    return $antrianHtml;
                })
                ->addColumn('no_rm', function($v) { return $v->no_rm ?? '-'; })
                ->addColumn('nama_pasien', function ($v) {
                    $nama = $v->nama_pasien ?? '-';
                    $icons = '';
                    
                    // Check patient age
                    if ($v->tanggal_lahir) {
                        $birthDate = new \DateTime($v->tanggal_lahir);
                        $today = new \DateTime();
                        $age = $today->diff($birthDate)->y;
                        if ($age < 17) {
                            $icons .= '<span class="status-pasien-icon" style="background-color: #ff69b4; color: white; padding: 2px 5px; border-radius: 3px; margin-right: 5px;" title="Pasien di bawah 17 tahun"><i class="fas fa-baby-carriage"></i></span>';
                        }
                    }
                    
                    return $icons . $nama;
                })
                ->addColumn('tanggal', function ($v) {
                    return \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                })
                ->addColumn('metode_bayar', function($v) { return $v->metodeBayar->nama ?? '-'; })
                ->addColumn('spesialisasi', function ($v) {
                    return ($v->dokter && $v->dokter->spesialisasi) ? $v->dokter->spesialisasi->nama : '-';
                })
                ->addColumn('dokter_nama', function ($v) {
                    return ($v->dokter && $v->dokter->user) ? $v->dokter->user->name : '-';
                })
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $dokumenBtn = '';
                    if ($user->hasRole('Perawat')) {
                        $visitationId = (string) $v->id;
                        if ($v->screeningBatuk) {
                            $dokumenBtn = '<a href="' . route('erm.asesmenperawat.create', $v->id) . '" class="btn btn-sm btn-primary ml-1" style="font-weight:bold;" title="Lihat"><i class="fas fa-eye mr-1"></i>Lihat</a>';
                            $dokumenBtn .= '<button class="btn btn-sm btn-info ml-1 view-screening-btn" style="font-weight:bold;" title="Screening Batuk" data-visitation-id="' . $visitationId . '"><i class="fas fa-lungs"></i></button>';
                        } else {
                            $dokumenBtn = '<button class="btn btn-sm btn-primary ml-1 screening-btn" style="font-weight:bold;" title="Lihat" data-visitation-id="' . $visitationId . '"><i class="fas fa-eye mr-1"></i>Lihat</button>';
                        }
                    } elseif ($user->hasRole('Dokter')) {
                        if ($v->status_dokumen === 'asesmen') {
                            $url = route('erm.asesmendokter.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-primary ml-1" style="font-weight:bold;" title="Asesmen"><i class="fas fa-user-md mr-1"></i>Asesmen</a>';
                        } elseif ($v->status_dokumen === 'cppt') {
                            $url = route('erm.cppt.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-success ml-1" style="font-weight:bold;" title="CPPT"><i class="fas fa-notes-medical mr-1"></i>CPPT</a>';
                        }
                    }
                    $additionalBtns = '';
                    if ($user->hasRole('Pendaftaran') || $user->hasRole('Perawat')) {
                        $dokterNama = $v->dokter->user->name ?? 'Dokter';
                        $tanggalKunjungan = \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                        $additionalBtns .= '<button class="btn btn-sm btn-success ml-1" style="font-weight:bold;" onclick="openKonfirmasiModal(`' .
                            $v->nama_pasien . '`, `' .
                            $v->telepon_pasien . '`, `' .
                            $dokterNama . '`, `' .
                            $tanggalKunjungan . '`, `' .
                            $v->no_antrian . '`, `' .
                            $v->gender . '`, `' .
                            $v->tanggal_lahir . '` )" title="WA Pasien"><i class=\'fab fa-whatsapp\'></i></button>';
                        $waktuKunjungan = $v->waktu_kunjungan ?? '';
                        $additionalBtns .= '<button class="btn btn-sm btn-info ml-1" style="font-weight:bold;" onclick="editAntrian(\'' . $v->id . '\', ' . $v->no_antrian . ', \'' . htmlspecialchars($waktuKunjungan, ENT_QUOTES, 'UTF-8') . '\')" title="Edit Antrian"><i class=\'fas fa-edit\'></i></button>';
                        $additionalBtns .= '<button class="btn btn-sm btn-danger ml-1" style="font-weight:bold;" onclick="batalkanKunjungan(\'' . $v->id . '\', this)" title="Batalkan"><i class=\'fas fa-times\'></i></button>';
                    }
                    return $dokumenBtn . ' ' . $additionalBtns;
                })
                ->addColumn('selesai_asesmen', function ($v) {
                    $asesmenPenunjang = $v->asesmenPenunjang;
                    $cppt = $v->cppt;
                    if ($asesmenPenunjang && $asesmenPenunjang->created_at) {
                        return $asesmenPenunjang->created_at->format('H:i');
                    } elseif ($cppt && $cppt->created_at) {
                        return $cppt->created_at->format('H:i');
                    } else {
                        return '-';
                    }
                })
                ->addColumn('waktu_kunjungan', function ($v) {
                    return $v->waktu_kunjungan ? substr($v->waktu_kunjungan, 0, 5) : '-';
                })
                ->rawColumns(['antrian', 'nama_pasien', 'dokumen'])
                ->make(true);
        }

        // Calculate statistics
        $stats = $this->getVisitationStats();
        
        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        $kliniks = Klinik::all();
        $role = Auth::user()->getRoleNames()->first();
        return view('erm.rawatjalans.index', compact('dokters', 'metodeBayar', 'role', 'kliniks', 'stats'));
    }

    /**
     * Get visitation statistics based on status_kunjungan
     */
    private function getVisitationStats()
    {
        $baseQuery = Visitation::whereIn('jenis_kunjungan', [1, 2]);
        
        // Apply same filters as in the DataTable query
        $user = Auth::user();
        if ($user->hasRole('Dokter')) {
            $dokter = Dokter::where('user_id', $user->id)->first();
            if ($dokter) {
                $baseQuery->where('dokter_id', $dokter->id);
            }
        }

        // Apply today's date filter by default
        $today = now()->format('Y-m-d');
        $baseQuery->whereDate('tanggal_visitation', $today);

        // Calculate statistics
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'tidak_datang' => (clone $baseQuery)->where('status_kunjungan', 0)->count(),
            'belum_diperiksa' => (clone $baseQuery)->where('status_kunjungan', 1)->count(),
            'sudah_diperiksa' => (clone $baseQuery)->where('status_kunjungan', 2)->count(),
            'dibatalkan' => (clone $baseQuery)->where('status_kunjungan', 7)->count(),
        ];

        return $stats;
    }

    /**
     * Get visitation statistics via AJAX for dynamic updates
     */
    public function getStats(Request $request)
    {
        $baseQuery = Visitation::whereIn('jenis_kunjungan', [1, 2]);
        
        // Apply same filters as in the DataTable query
        $user = Auth::user();
        if ($user->hasRole('Dokter')) {
            $dokter = Dokter::where('user_id', $user->id)->first();
            if ($dokter) {
                $baseQuery->where('dokter_id', $dokter->id);
            }
        }

        // Filter by klinik_id if provided
        if ($request->klinik_id) {
            $baseQuery->where('klinik_id', $request->klinik_id);
        }

        // Date range filter
        if ($request->start_date && $request->end_date) {
            $baseQuery->whereDate('tanggal_visitation', '>=', $request->start_date)
                     ->whereDate('tanggal_visitation', '<=', $request->end_date);
        } else {
            // Default to today if no date filter
            $today = now()->format('Y-m-d');
            $baseQuery->whereDate('tanggal_visitation', $today);
        }

        if ($request->dokter_id) {
            $baseQuery->where('dokter_id', $request->dokter_id);
        }

        // Calculate statistics including cancelled visits for total count
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'tidak_datang' => (clone $baseQuery)->where('status_kunjungan', 0)->count(),
            'belum_diperiksa' => (clone $baseQuery)->where('status_kunjungan', 1)->count(),
            'sudah_diperiksa' => (clone $baseQuery)->where('status_kunjungan', 2)->count(),
            'dibatalkan' => (clone $baseQuery)->where('status_kunjungan', 7)->count(),
        ];

        return response()->json($stats);
    }

    public function cekAntrian(Request $request)
    {
        $tanggal = $request->tanggal;
        $dokter_id = $request->dokter_id;

        // Get the current max antrian number for the date and doctor
        $max = Visitation::whereDate('tanggal_visitation', $tanggal)
            ->where('dokter_id', $dokter_id)
            ->max('no_antrian');

        // Numbers to skip
        $skip = [3, 5];
        $next = ($max ?? 0) + 1;
        while (in_array($next, $skip)) {
            $next++;
        }

        return response()->json([
            'no_antrian' => $next
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'dokter_id' => 'required',
            'tanggal_visitation' => 'required|date',
            'no_antrian' => 'required|integer',
            'waktu_kunjungan' => 'nullable|date_format:H:i', // validate waktu_kunjungan
        ]);

        // update kunjungan lama jadi progress 7
        if ($request->has('visitation_id')) {
            Visitation::where('id', $request->visitation_id)->update([
                'status_kunjungan' => 7
            ]);
        }

        // buat kunjungan baru
        Visitation::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'waktu_kunjungan' => $request->waktu_kunjungan, // save waktu_kunjungan
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id ?? 1,
            'status_kunjungan' => 0,
        ]);

        return response()->json(['message' => 'Berhasil menjadwalkan ulang pasien.']);
    }

    // Batalkan kunjungan
    public function batalkan(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
        ]);
        $visitation = Visitation::findOrFail($request->visitation_id);
        $visitation->status_kunjungan = 7;
        $visitation->no_antrian = null;
        $visitation->save();
        return response()->json(['success' => true]);
    }

    // Edit antrian
    public function editAntrian(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'no_antrian' => 'required|integer|min:1',
            'waktu_kunjungan' => 'nullable|date_format:H:i', // allow editing waktu_kunjungan
        ]);
        $visitation = Visitation::findOrFail($request->visitation_id);
        $visitation->no_antrian = $request->no_antrian;
        if ($request->has('waktu_kunjungan')) {
            $visitation->waktu_kunjungan = $request->waktu_kunjungan;
        }
        $visitation->save();
        return response()->json(['success' => true]);
    }

    // Store Screening Batuk
    public function storeScreeningBatuk(Request $request)
    {
        // Debug: Log the request data
        Log::info('Screening Batuk Request Data:', $request->all());
        
        $request->validate([
            'visitation_id' => 'required|string',
            // Sesi Gejala
            'demam_badan_panas' => 'required|in:ya,tidak',
            'batuk_pilek' => 'required|in:ya,tidak',
            'sesak_nafas' => 'required|in:ya,tidak',
            'kontak_covid' => 'required|in:ya,tidak',
            'perjalanan_luar_negeri' => 'required|in:ya,tidak',
            // Sesi Faktor Resiko
            'riwayat_perjalanan' => 'required|in:ya,tidak',
            'kontak_erat_covid' => 'required|in:ya,tidak',
            'faskes_covid' => 'required|in:ya,tidak',
            'kontak_hewan' => 'required|in:ya,tidak',
            'riwayat_demam' => 'required|in:ya,tidak',
            'riwayat_kontak_luar_negeri' => 'required|in:ya,tidak',
            // Sesi Tools Screening Batuk
            'riwayat_pengobatan_tb' => 'required|in:ya,tidak',
            'sedang_pengobatan_tb' => 'required|in:ya,tidak',
            'batuk_demam' => 'required|in:ya,tidak',
            'nafsu_makan_menurun' => 'required|in:ya,tidak',
            'bb_turun' => 'required|in:ya,tidak',
            'keringat_malam' => 'required|in:ya,tidak',
            'sesak_nafas_tb' => 'required|in:ya,tidak',
            'kontak_erat_tb' => 'required|in:ya,tidak',
            'hasil_rontgen' => 'required|in:ya,tidak',
            // Others
            'catatan' => 'nullable|string|max:1000'
        ]);

        try {
            // Check if visitation exists
            $visitation = Visitation::find($request->visitation_id);
            Log::info('Looking for visitation with ID: ' . $request->visitation_id);
            Log::info('Visitation found: ' . ($visitation ? 'Yes' : 'No'));
            
            if (!$visitation) {
                // Let's also try to see if there are any similar IDs in the database
                $similarVisitations = Visitation::where('id', 'LIKE', '%' . substr($request->visitation_id, -10) . '%')->limit(5)->get(['id']);
                Log::info('Similar visitations found: ', $similarVisitations->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Kunjungan tidak ditemukan dengan ID: ' . $request->visitation_id
                ], 404);
            }

            // Check if screening already exists for this visitation
            $existingScreening = ScreeningBatuk::where('visitation_id', $request->visitation_id)->first();
            
            if ($existingScreening) {
                // Update existing screening
                $existingScreening->update([
                    // Sesi Gejala
                    'demam_badan_panas' => $request->demam_badan_panas,
                    'batuk_pilek' => $request->batuk_pilek,
                    'sesak_nafas' => $request->sesak_nafas,
                    'kontak_covid' => $request->kontak_covid,
                    'perjalanan_luar_negeri' => $request->perjalanan_luar_negeri,
                    // Sesi Faktor Resiko
                    'riwayat_perjalanan' => $request->riwayat_perjalanan,
                    'kontak_erat_covid' => $request->kontak_erat_covid,
                    'faskes_covid' => $request->faskes_covid,
                    'kontak_hewan' => $request->kontak_hewan,
                    'riwayat_demam' => $request->riwayat_demam,
                    'riwayat_kontak_luar_negeri' => $request->riwayat_kontak_luar_negeri,
                    // Sesi Tools Screening Batuk
                    'riwayat_pengobatan_tb' => $request->riwayat_pengobatan_tb,
                    'sedang_pengobatan_tb' => $request->sedang_pengobatan_tb,
                    'batuk_demam' => $request->batuk_demam,
                    'nafsu_makan_menurun' => $request->nafsu_makan_menurun,
                    'bb_turun' => $request->bb_turun,
                    'keringat_malam' => $request->keringat_malam,
                    'sesak_nafas_tb' => $request->sesak_nafas_tb,
                    'kontak_erat_tb' => $request->kontak_erat_tb,
                    'hasil_rontgen' => $request->hasil_rontgen,
                    // Others
                    'catatan' => $request->catatan,
                    'created_by' => Auth::id()
                ]);
            } else {
                // Create new screening record
                ScreeningBatuk::create([
                    'visitation_id' => $request->visitation_id,
                    // Sesi Gejala
                    'demam_badan_panas' => $request->demam_badan_panas,
                    'batuk_pilek' => $request->batuk_pilek,
                    'sesak_nafas' => $request->sesak_nafas,
                    'kontak_covid' => $request->kontak_covid,
                    'perjalanan_luar_negeri' => $request->perjalanan_luar_negeri,
                    // Sesi Faktor Resiko
                    'riwayat_perjalanan' => $request->riwayat_perjalanan,
                    'kontak_erat_covid' => $request->kontak_erat_covid,
                    'faskes_covid' => $request->faskes_covid,
                    'kontak_hewan' => $request->kontak_hewan,
                    'riwayat_demam' => $request->riwayat_demam,
                    'riwayat_kontak_luar_negeri' => $request->riwayat_kontak_luar_negeri,
                    // Sesi Tools Screening Batuk
                    'riwayat_pengobatan_tb' => $request->riwayat_pengobatan_tb,
                    'sedang_pengobatan_tb' => $request->sedang_pengobatan_tb,
                    'batuk_demam' => $request->batuk_demam,
                    'nafsu_makan_menurun' => $request->nafsu_makan_menurun,
                    'bb_turun' => $request->bb_turun,
                    'keringat_malam' => $request->keringat_malam,
                    'sesak_nafas_tb' => $request->sesak_nafas_tb,
                    'kontak_erat_tb' => $request->kontak_erat_tb,
                    'hasil_rontgen' => $request->hasil_rontgen,
                    // Others
                    'catatan' => $request->catatan,
                    'created_by' => Auth::id()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data screening batuk berhasil disimpan.'
            ]);

        } catch (\Exception $e) {
            Log::error('Screening Batuk Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getScreeningBatuk($visitationId)
    {
        try {
            $screening = ScreeningBatuk::where('visitation_id', $visitationId)->first();
            
            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data screening batuk tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $screening
            ]);

        } catch (\Exception $e) {
            Log::error('Get Screening Batuk Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateScreeningBatuk(Request $request, $id)
    {
        // Debug: Log the request data
        Log::info('Update Screening Batuk Request Data:', $request->all());
        Log::info('Screening ID:', [$id]);
        
        $request->validate([
            'visitation_id' => 'required|string',
            // Sesi Gejala
            'demam_badan_panas' => 'required|in:ya,tidak',
            'batuk_pilek' => 'required|in:ya,tidak',
            'sesak_nafas' => 'required|in:ya,tidak',
            'kontak_covid' => 'required|in:ya,tidak',
            'perjalanan_luar_negeri' => 'required|in:ya,tidak',
            // Sesi Faktor Resiko
            'riwayat_perjalanan' => 'required|in:ya,tidak',
            'kontak_erat_covid' => 'required|in:ya,tidak',
            'faskes_covid' => 'required|in:ya,tidak',
            'kontak_hewan' => 'required|in:ya,tidak',
            'riwayat_demam' => 'required|in:ya,tidak',
            'riwayat_kontak_luar_negeri' => 'required|in:ya,tidak',
            // Sesi Tools Screening Batuk
            'riwayat_pengobatan_tb' => 'required|in:ya,tidak',
            'sedang_pengobatan_tb' => 'required|in:ya,tidak',
            'batuk_demam' => 'required|in:ya,tidak',
            'nafsu_makan_menurun' => 'required|in:ya,tidak',
            'bb_turun' => 'required|in:ya,tidak',
            'keringat_malam' => 'required|in:ya,tidak',
            'sesak_nafas_tb' => 'required|in:ya,tidak',
            'kontak_erat_tb' => 'required|in:ya,tidak',
            'hasil_rontgen' => 'required|in:ya,tidak',
            // Others
            'catatan' => 'nullable|string|max:1000'
        ]);

        try {
            // Find the screening record
            $screening = ScreeningBatuk::find($id);
            
            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data screening batuk tidak ditemukan'
                ], 404);
            }

            // Update the screening data
            $screening->update([
                'visitation_id' => $request->visitation_id,
                // Sesi Gejala
                'demam_badan_panas' => $request->demam_badan_panas,
                'batuk_pilek' => $request->batuk_pilek,
                'sesak_nafas' => $request->sesak_nafas,
                'kontak_covid' => $request->kontak_covid,
                'perjalanan_luar_negeri' => $request->perjalanan_luar_negeri,
                // Sesi Faktor Resiko
                'riwayat_perjalanan' => $request->riwayat_perjalanan,
                'kontak_erat_covid' => $request->kontak_erat_covid,
                'faskes_covid' => $request->faskes_covid,
                'kontak_hewan' => $request->kontak_hewan,
                'riwayat_demam' => $request->riwayat_demam,
                'riwayat_kontak_luar_negeri' => $request->riwayat_kontak_luar_negeri,
                // Sesi Tools Screening Batuk
                'riwayat_pengobatan_tb' => $request->riwayat_pengobatan_tb,
                'sedang_pengobatan_tb' => $request->sedang_pengobatan_tb,
                'batuk_demam' => $request->batuk_demam,
                'nafsu_makan_menurun' => $request->nafsu_makan_menurun,
                'bb_turun' => $request->bb_turun,
                'keringat_malam' => $request->keringat_malam,
                'sesak_nafas_tb' => $request->sesak_nafas_tb,
                'kontak_erat_tb' => $request->kontak_erat_tb,
                'hasil_rontgen' => $request->hasil_rontgen,
                // Others
                'catatan' => $request->catatan,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data screening batuk berhasil diperbarui.',
                'data' => $screening
            ]);

        } catch (\Exception $e) {
            Log::error('Update Screening Batuk Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }
        /**
     * AJAX: Get list of visitations by status for stats modal
     */
    public function listByStatus(Request $request)
    {
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');

        $query = Visitation::query()
            ->with(['pasien', 'dokter'])
            ->whereIn('jenis_kunjungan', [1, 2]);

        // Status filter
        if ($status && $status !== 'total') {
            $map = [
                'tidak_datang' => 0,
                'belum_diperiksa' => 1,
                'sudah_diperiksa' => 2,
                'dibatalkan' => 7
            ];
            if (isset($map[$status])) {
                $query->where('status_kunjungan', $map[$status]);
            }
        }

        // Date range filter
        if ($startDate && $endDate) {
            $query->whereDate('tanggal_visitation', '>=', $startDate)
                  ->whereDate('tanggal_visitation', '<=', $endDate);
        }

        // Dokter filter
        if ($dokterId) {
            $query->where('dokter_id', $dokterId);
        }

        // Klinik filter
        if ($klinikId) {
            $query->where('klinik_id', $klinikId);
        }

        $visitations = $query->orderBy('tanggal_visitation', 'desc')->limit(100)->get();

        $data = $visitations->map(function($v) {
            return [
                'id' => $v->id,
                'pasien_nama' => $v->pasien ? $v->pasien->nama : '-',
                'dokter_nama' => $v->dokter ? ($v->dokter->user->name ?? '-') : '-',
                'tanggal_visitation' => $v->tanggal_visitation,
                'no_antrian' => $v->no_antrian,
            ];
        });

        return response()->json(['data' => $data]);
    }

}
