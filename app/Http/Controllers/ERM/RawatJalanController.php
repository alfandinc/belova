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
use App\Models\ERM\Rujuk;
use App\Models\ERM\LabPermintaan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\ERM\PasienMerchandise;

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
        Log::info('Perawat IDs:', $perawats->pluck('id')->toArray());
        foreach ($perawats as $perawat) {
            Log::info('Sending notification to Perawat ID: ' . $perawat->id);
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
            // If request is AJAX and user not authenticated, respond with JSON 401 instead of redirecting
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            try {
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
                // include merchandise count per pasien using subquery
                ->selectRaw('(SELECT COUNT(1) FROM erm_pasien_merchandises WHERE erm_pasien_merchandises.pasien_id = erm_pasiens.id) as merchandise_count')
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
                ->where('erm_visitations.status_kunjungan', '!=', 7);

            if ($request->filled('klinik_id')) {
                $visitations->where('erm_visitations.klinik_id', $request->klinik_id);
            }
            $user = Auth::user();
            // Default behavior: if the logged-in user has role Dokter and no explicit dokter filter
            // is provided, show visitations for that logged-in dokter. This applies even if the user
            // also has the Admin role. If a dokter is selected via the filter (dokter_id), that selection wins.
            if ($user && $user->hasRole('Dokter') && !$request->filled('dokter_id')) {
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
            // Eager load lightweight relations and use withCount for collection counts to reduce memory
            $visitations->with([
                'metodeBayar:id,nama',
                'dokter.user:id,name',
                'dokter.spesialisasi:id,nama',
                'screeningBatuk:id,visitation_id',
                'asesmenPenunjang:id,visitation_id,created_at',
                'cppt:id,visitation_id,created_at'
            ])->withCount([
                'labPermintaan as lab_permintaan_count',
                'riwayatTindakan as riwayat_tindakan_count'
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
                    if (isset($v->lab_permintaan_count) && $v->lab_permintaan_count > 0) {
                        $antrianHtml .= ' <i class="fas fa-flask blinking" title="Ada permintaan lab"></i>';
                    }
                    if (isset($v->riwayat_tindakan_count) && $v->riwayat_tindakan_count > 0) {
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
                    
                    // append a shopping-bag icon if pasien has merchandise
                    $merchCount = intval($v->merchandise_count ?? 0);
                    if ($merchCount > 0) {
                        // Styled badge like other status icons: small colored square with white icon
                        $icons .= ' <a href="#" class="ml-1 pasien-merch" data-pasien-id="' . $v->pasien_id . '" title="Lihat merchandise yang diterima">'
                            . '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;background-color:#1E90FF;border-radius:3px;color:#fff;">'
                            . '<i class="fas fa-shopping-bag" style="font-size:11px;color:#fff"></i>'
                            . '</span></a>';
                    }
                    return $icons . ' ' . $nama;
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
            } catch (\Exception $e) {
                Log::error('RawatJalanController@index AJAX error: ' . $e->getMessage(), ['exception' => $e]);
                return response()->json(['error' => 'Internal Server Error'], 500);
            }
        }

        // Calculate statistics
        $stats = $this->getVisitationStats();
        
        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        $kliniks = Klinik::all();
        $role = Auth::user()->getRoleNames()->first();
        // Determine default dokter selection: if the logged-in user has Dokter role,
        // default the filter to their Dokter record so the page shows their visits by default.
        $defaultDokterId = null;
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->hasRole('Dokter')) {
            $myDokter = Dokter::where('user_id', $currentUser->id)->first();
            if ($myDokter) {
                $defaultDokterId = $myDokter->id;
            }
        }
        return view('erm.rawatjalans.index', compact('dokters', 'metodeBayar', 'role', 'kliniks', 'stats', 'defaultDokterId'));
    }

    /**
     * Get visitation statistics based on status_kunjungan
     */
    private function getVisitationStats()
    {
        // Build cache key based on date and current user/dokter restriction
        $user = Auth::user();
        $dokter = null;
        if ($user && $user->hasRole('Dokter') && !$user->hasRole('Admin')) {
            $dokter = Dokter::where('user_id', $user->id)->first();
        }

        $today = now()->format('Y-m-d');
        $cacheKey = 'visitation_stats_' . $today . '_dok_' . ($dokter ? $dokter->id : 'all');

        return Cache::remember($cacheKey, 5, function() use ($today, $dokter) {
            // Use a single aggregated query to compute counts
            $query = DB::table('erm_visitations')
                ->selectRaw(
                    "COUNT(*) as total,
                    SUM(CASE WHEN status_kunjungan = 0 THEN 1 ELSE 0 END) as tidak_datang,
                    SUM(CASE WHEN status_kunjungan = 1 THEN 1 ELSE 0 END) as belum_diperiksa,
                    SUM(CASE WHEN status_kunjungan = 2 THEN 1 ELSE 0 END) as sudah_diperiksa,
                    SUM(CASE WHEN status_kunjungan = 7 THEN 1 ELSE 0 END) as dibatalkan"
                )
                ->whereIn('jenis_kunjungan', [1,2])
                ->whereDate('tanggal_visitation', $today);

            if ($dokter) {
                $query->where('dokter_id', $dokter->id);
            }

            $row = $query->first();

            $rujukQuery = Rujuk::whereHas('visitation', function($q) use ($today) {
                $q->whereDate('tanggal_visitation', $today);
            });
            if ($dokter) {
                $rujukQuery->where(function($r) use ($dokter) {
                    $r->where('dokter_pengirim_id', $dokter->id)
                      ->orWhere('dokter_tujuan_id', $dokter->id)
                      ->orWhereHas('visitation', function($v) use ($dokter) {
                          $v->where('dokter_id', $dokter->id);
                      });
                });
            }

            return [
                'total' => $row->total ?? 0,
                'tidak_datang' => $row->tidak_datang ?? 0,
                'belum_diperiksa' => $row->belum_diperiksa ?? 0,
                'sudah_diperiksa' => $row->sudah_diperiksa ?? 0,
                'dibatalkan' => $row->dibatalkan ?? 0,
                'rujuk' => $rujukQuery->count(),
            ];
        });
    }

    /**
     * Get visitation statistics via AJAX for dynamic updates
     */
    public function getStats(Request $request)
    {
        try {
            // existing optimized implementation
        
            // Prepare filters
            $user = Auth::user();
            $dokter = null;
            if ($user && $user->hasRole('Dokter')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
            }

            $start = $request->start_date;
            $end = $request->end_date;
            $today = now()->format('Y-m-d');
            if (!$start || !$end) {
                $start = $end = $today;
            }

            // If explicit dokter_id provided, use that and clear default dokter
            if ($request->dokter_id) {
                $dokter = null;
                $dokterFilter = $request->dokter_id;
            } else {
                $dokterFilter = ($dokter ? $dokter->id : null);
            }

            $klinikFilter = $request->klinik_id ?? null;

            // Create cache key based on filters
            $cacheKey = 'getstats_' . $start . '_' . $end . '_dok_' . ($dokterFilter ?? 'all') . '_klinik_' . ($klinikFilter ?? 'all');

            $stats = Cache::remember($cacheKey, 5, function() use ($start, $end, $dokterFilter, $klinikFilter) {
                $query = DB::table('erm_visitations')
                    ->selectRaw(
                        "COUNT(*) as total,
                        SUM(CASE WHEN status_kunjungan = 0 THEN 1 ELSE 0 END) as tidak_datang,
                        SUM(CASE WHEN status_kunjungan = 1 THEN 1 ELSE 0 END) as belum_diperiksa,
                        SUM(CASE WHEN status_kunjungan = 2 THEN 1 ELSE 0 END) as sudah_diperiksa,
                        SUM(CASE WHEN status_kunjungan = 7 THEN 1 ELSE 0 END) as dibatalkan"
                    )
                    ->whereIn('jenis_kunjungan', [1,2])
                    ->whereDate('tanggal_visitation', '>=', $start)
                    ->whereDate('tanggal_visitation', '<=', $end);

                if ($dokterFilter) {
                    $query->where('dokter_id', $dokterFilter);
                }
                if ($klinikFilter) {
                    $query->where('klinik_id', $klinikFilter);
                }

                $row = $query->first();

                // rujuk count for the requested date range
                $rujukQuery = Rujuk::whereHas('visitation', function($q) use ($start) {
                    $q->whereDate('tanggal_visitation', $start);
                });
                if ($dokterFilter) {
                    $rujukQuery->where(function($r) use ($dokterFilter) {
                        $r->where('dokter_pengirim_id', $dokterFilter)
                          ->orWhere('dokter_tujuan_id', $dokterFilter)
                          ->orWhereHas('visitation', function($v) use ($dokterFilter) {
                              $v->where('dokter_id', $dokterFilter);
                          });
                    });
                }

                // Lab permintaan count within same date range & filters
                $labQuery = LabPermintaan::whereHas('visitation', function($q) use ($start, $end) {
                    $q->whereDate('tanggal_visitation', '>=', $start)
                      ->whereDate('tanggal_visitation', '<=', $end);
                });
                if ($dokterFilter) {
                    $labQuery->where('dokter_id', $dokterFilter);
                }
                if ($klinikFilter) {
                    $labQuery->whereHas('visitation', function($q) use ($klinikFilter) {
                        $q->where('klinik_id', $klinikFilter);
                    });
                }

                return [
                    'total' => $row->total ?? 0,
                    'tidak_datang' => $row->tidak_datang ?? 0,
                    'belum_diperiksa' => $row->belum_diperiksa ?? 0,
                    'sudah_diperiksa' => $row->sudah_diperiksa ?? 0,
                    'dibatalkan' => $row->dibatalkan ?? 0,
                    'rujuk' => $rujukQuery->count(),
                    'lab_permintaan' => $labQuery->count(),
                ];
            });

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('RawatJalanController@getStats error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        // Prepare filters
        $user = Auth::user();
        $dokter = null;
        if ($user && $user->hasRole('Dokter')) {
            $dokter = Dokter::where('user_id', $user->id)->first();
        }

        $start = $request->start_date;
        $end = $request->end_date;
        $today = now()->format('Y-m-d');
        if (!$start || !$end) {
            $start = $end = $today;
        }

        // If explicit dokter_id provided, use that and clear default dokter
        if ($request->dokter_id) {
            $dokter = null;
            $dokterFilter = $request->dokter_id;
        } else {
            $dokterFilter = ($dokter ? $dokter->id : null);
        }

        $klinikFilter = $request->klinik_id ?? null;

        // Create cache key based on filters
        $cacheKey = 'getstats_' . $start . '_' . $end . '_dok_' . ($dokterFilter ?? 'all') . '_klinik_' . ($klinikFilter ?? 'all');

    $stats = Cache::remember($cacheKey, 5, function() use ($start, $end, $dokterFilter, $klinikFilter) {
            $query = DB::table('erm_visitations')
                ->selectRaw(
                    "COUNT(*) as total,
                    SUM(CASE WHEN status_kunjungan = 0 THEN 1 ELSE 0 END) as tidak_datang,
                    SUM(CASE WHEN status_kunjungan = 1 THEN 1 ELSE 0 END) as belum_diperiksa,
                    SUM(CASE WHEN status_kunjungan = 2 THEN 1 ELSE 0 END) as sudah_diperiksa,
                    SUM(CASE WHEN status_kunjungan = 7 THEN 1 ELSE 0 END) as dibatalkan"
                )
                ->whereIn('jenis_kunjungan', [1,2])
                ->whereDate('tanggal_visitation', '>=', $start)
                ->whereDate('tanggal_visitation', '<=', $end);

            if ($dokterFilter) {
                $query->where('dokter_id', $dokterFilter);
            }
            if ($klinikFilter) {
                $query->where('klinik_id', $klinikFilter);
            }

            $row = $query->first();

            // rujuk count for the requested date range
            $rujukQuery = Rujuk::whereHas('visitation', function($q) use ($start) {
                $q->whereDate('tanggal_visitation', $start);
            });
            if ($dokterFilter) {
                $rujukQuery->where(function($r) use ($dokterFilter) {
                    $r->where('dokter_pengirim_id', $dokterFilter)
                      ->orWhere('dokter_tujuan_id', $dokterFilter)
                      ->orWhereHas('visitation', function($v) use ($dokterFilter) {
                          $v->where('dokter_id', $dokterFilter);
                      });
                });
            }

            // Lab permintaan count honoring same filters (date range + dokter + klinik)
            $labQuery = LabPermintaan::whereHas('visitation', function($q) use ($start, $end) {
                $q->whereDate('tanggal_visitation', '>=', $start)
                  ->whereDate('tanggal_visitation', '<=', $end);
            });
            if ($dokterFilter) {
                $labQuery->where('dokter_id', $dokterFilter);
            }
            if ($klinikFilter) {
                $labQuery->whereHas('visitation', function($q) use ($klinikFilter) {
                    $q->where('klinik_id', $klinikFilter);
                });
            }

            return [
                'total' => $row->total ?? 0,
                'tidak_datang' => $row->tidak_datang ?? 0,
                'belum_diperiksa' => $row->belum_diperiksa ?? 0,
                'sudah_diperiksa' => $row->sudah_diperiksa ?? 0,
                'dibatalkan' => $row->dibatalkan ?? 0,
                'rujuk' => $rujukQuery->count(),
                'lab_permintaan' => $labQuery->count(),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Return list of rujuk records for AJAX modal
     */
    public function listRujuks(Request $request)
    {
        $query = Rujuk::with(['pasien:id,nama,tanggal_lahir', 'dokterPengirim.user:id,name', 'dokterTujuan.user:id,name', 'visitation'])
            ->orderBy('created_at', 'desc');

        // If request included dokter_id, filter by involvement (pengirim/tujuan/visitation)
        if ($request->dokter_id) {
            $dokId = $request->dokter_id;
            $query->where(function($qr) use ($dokId) {
                $qr->where('dokter_pengirim_id', $dokId)
                   ->orWhere('dokter_tujuan_id', $dokId)
                   ->orWhereHas('visitation', function($v) use ($dokId) {
                        $v->where('dokter_id', $dokId);
                   });
            });
        }

        // If no explicit dokter filter and logged-in user is a Dokter, restrict to their involvement
    $user = Auth::user();
    if (!$request->dokter_id && $user && $user->hasRole('Dokter') && !$user->hasRole('Admin')) {
            $dokter = Dokter::where('user_id', $user->id)->first();
            if ($dokter) {
                $query->where(function($qr) use ($dokter) {
                    $qr->where('dokter_pengirim_id', $dokter->id)
                       ->orWhere('dokter_tujuan_id', $dokter->id)
                       ->orWhereHas('visitation', function($v) use ($dokter) {
                            $v->where('dokter_id', $dokter->id);
                       });
                });
            }
        }

        // Filter by related visitation.tanggal_visitation
        if ($request->start_date && $request->end_date) {
            $query->whereHas('visitation', function($q) use ($request) {
                $q->whereDate('tanggal_visitation', '>=', $request->start_date)
                  ->whereDate('tanggal_visitation', '<=', $request->end_date);
            });
        } else {
            $today = now()->format('Y-m-d');
            $query->whereHas('visitation', function($q) use ($today) {
                $q->whereDate('tanggal_visitation', $today);
            });
        }

        if ($request->dokter_id) {
            $query->where('dokter_tujuan_id', $request->dokter_id);
        }

        $rujuks = $query->get();

        return response()->json(['data' => $rujuks]);
    }

    /**
     * Return list of Lab Permintaan (lab requests) for the stats modal
     */
    public function listLabPermintaan(Request $request)
    {
        try {
            $start = $request->start_date ?: now()->format('Y-m-d');
            $end = $request->end_date ?: $start;
            $dokterFilter = $request->dokter_id;
            $klinikFilter = $request->klinik_id;

            $query = LabPermintaan::with([
                'visitation:id,pasien_id,dokter_id,klinik_id,tanggal_visitation,no_antrian',
                'visitation.pasien:id,nama',
                'labTest:id,nama',
                'dokter.user:id,name'
            ])->whereHas('visitation', function($q) use ($start, $end) {
                $q->whereDate('tanggal_visitation', '>=', $start)
                  ->whereDate('tanggal_visitation', '<=', $end);
            });

            if ($dokterFilter) {
                $query->where('dokter_id', $dokterFilter);
            }
            if ($klinikFilter) {
                $query->whereHas('visitation', function($q) use ($klinikFilter) {
                    $q->where('klinik_id', $klinikFilter);
                });
            }

            $labsRaw = $query->orderBy('created_at','desc')->get();

            // Group by visitation_id
            $grouped = $labsRaw->groupBy('visitation_id')->map(function($collection) {
                $first = $collection->first();
                $tests = $collection->map(function($l){
                    // Processing duration per test
                    $processMinutes = null; $processSeconds = null; $processHuman = null;
                    if ($l->processed_at && $l->completed_at) {
                        $processSeconds = $l->completed_at->diffInSeconds($l->processed_at);
                        $processMinutes = (int) floor($processSeconds / 60);
                        // human friendly (e.g. "2m 15s" or "35s")
                        $remaining = $processSeconds % 60;
                        if ($processMinutes > 0) {
                            $processHuman = $processMinutes . 'm' . ($remaining ? ' ' . $remaining . 's' : '');
                        } else {
                            $processHuman = $remaining . 's';
                        }
                    }
                    return [
                        'name' => $l->labTest?->nama ?? '-',
                        'status' => $l->status ?? '-',
                        'processed_at' => optional($l->processed_at)->format('Y-m-d H:i:s'),
                        'completed_at' => optional($l->completed_at)->format('Y-m-d H:i:s'),
                        'process_time_minutes' => $processMinutes,
                        'process_time_seconds' => $processSeconds,
                        'process_time_human' => $processHuman,
                    ];
                })->values();
                $statuses = $collection->map(function($l){ return $l->status ?? '-'; })->unique()->values(); // kept if needed for other views

                // Aggregate earliest processed and latest completed for visitation scope (optional UI usage)
                $earliestProcessed = $collection->filter(fn($l) => $l->processed_at)->min('processed_at');
                $latestCompleted = $collection->filter(fn($l) => $l->completed_at)->max('completed_at');
                $aggregateProcessMinutes = null; $aggregateProcessSeconds = null; $aggregateProcessHuman = null;
                if ($earliestProcessed && $latestCompleted) {
                    $aggregateProcessSeconds = $latestCompleted->diffInSeconds($earliestProcessed);
                    $aggregateProcessMinutes = (int) floor($aggregateProcessSeconds / 60);
                    $remainingAgg = $aggregateProcessSeconds % 60;
                    if ($aggregateProcessMinutes > 0) {
                        $aggregateProcessHuman = $aggregateProcessMinutes . 'm' . ($remainingAgg ? ' ' . $remainingAgg . 's' : '');
                    } else {
                        $aggregateProcessHuman = $remainingAgg . 's';
                    }
                }

                return [
                    'visitation_id' => $first->visitation_id,
                    'pasien' => $first->visitation?->pasien?->nama ?? '-',
                    'lab_tests' => $tests,
                    'statuses' => $statuses, // legacy aggregate
                    'dokter' => $first->dokter?->user?->name ?? '-',
                    'tanggal' => optional($first->visitation)->tanggal_visitation,
                    'no_antrian' => optional($first->visitation)->no_antrian,
                    'created_at' => optional($first->created_at)->format('Y-m-d H:i:s'),
                    'duration_waiting' => $first->duration_waiting,
                    'duration_processing' => $first->duration_processing,
                    'duration_total' => $first->duration_total,
                    'aggregate_processed_at' => $earliestProcessed ? $earliestProcessed->format('Y-m-d H:i:s') : null,
                    'aggregate_completed_at' => $latestCompleted ? $latestCompleted->format('Y-m-d H:i:s') : null,
                    'aggregate_process_time_minutes' => $aggregateProcessMinutes,
                    'aggregate_process_time_seconds' => $aggregateProcessSeconds,
                    'aggregate_process_time_human' => $aggregateProcessHuman,
                ];
            })->values();

            return response()->json(['data' => $grouped]);
        } catch(\Exception $e) {
            Log::error('listLabPermintaan error', ['msg'=>$e->getMessage()]);
            return response()->json(['data' => [], 'error' => 'Internal Server Error'], 500);
        }
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

    /**
     * Return list of merchandises received by a patient (AJAX)
     */
    public function getPasienMerchandises($pasienId)
    {
        try {
            $items = PasienMerchandise::with(['merchandise:id,name,description,price,stock', 'pasien:id,nama'])
                ->where('pasien_id', $pasienId)
                ->orderBy('given_at', 'desc')
                ->get()
                ->map(function($m) {
                    return [
                        'id' => $m->id,
                        'nama' => $m->merchandise->name ?? '-',
                        'description' => $m->merchandise->description ?? null,
                        'quantity' => $m->quantity,
                        'notes' => $m->notes,
                        'given_by' => $m->given_by_user_id,
                        'given_at' => $m->given_at ? (is_string($m->given_at) ? $m->given_at : $m->given_at->format('Y-m-d H:i')) : null,
                    ];
                });

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            Log::error('getPasienMerchandises error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
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
        } else {
            // If logged in user is a Dokter and no dokter filter provided, restrict to that dokter
            $user = Auth::user();
            if ($user && $user->hasRole('Dokter') && !$user->hasRole('Admin')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $query->where('dokter_id', $dokter->id);
                }
            }
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
