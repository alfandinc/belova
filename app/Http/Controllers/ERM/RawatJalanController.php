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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\ERM\PasienMerchandise;
use App\Models\WaMessage;
use App\Models\WaScheduledMessage;
use App\Services\VisitationWhatsAppScheduler;
use App\Notifications\DokterToPerawatNotification;
use App\Notifications\PerawatToDokterNotification;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class RawatJalanController extends Controller
{
    /**
     * Lazy-loaded modal HTML for common Rawat Jalan modals.
     * Kept server-rendered so the UI markup stays identical to the original Blade.
     */
    public function commonModals()
    {
        if (!Auth::check()) {
            return response('Unauthenticated', 401);
        }

        $metodeBayar = Cache::remember('erm_metode_bayar', 300, function () {
            return MetodeBayar::select('id', 'nama')->orderBy('nama')->get();
        });

        return response()->view('erm.rawatjalans.partials.common_modals', compact('metodeBayar'));
    }

    /**
     * Lazy-loaded modal HTML for Screening Batuk.
     * Kept server-rendered so the UI markup stays identical to the original Blade.
     */
    public function screeningBatukModals()
    {
        if (!Auth::check()) {
            return response('Unauthenticated', 401);
        }

        return response()->view('erm.rawatjalans.partials.screening_batuk_modals');
    }

    /**
     * Serve Rawat Jalan page JavaScript as a Blade-rendered .js resource.
     * This keeps initial HTML small while preserving existing Blade-driven URLs/tokens.
     */
    public function assetsJs()
    {
        if (!Auth::check()) {
            return response('Unauthenticated', 401);
        }

        $dokters = Cache::remember('erm_dokters_list', 300, function() {
            return Dokter::select('id', 'user_id', 'spesialisasi_id')
                ->with(['user:id,name', 'spesialisasi:id,nama'])
                ->get();
        });

        $metodeBayar = Cache::remember('erm_metode_bayar', 300, function() {
            return MetodeBayar::select('id', 'nama')->get();
        });

        $role = Auth::user()->getRoleNames()->first();
        $isDokter = Auth::user()->hasRole('Dokter');

        $defaultDokterId = null;
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->hasRole('Dokter')) {
            $myDokter = Dokter::where('user_id', $currentUser->id)->first();
            if ($myDokter) {
                $defaultDokterId = $myDokter->id;
            }
        }

        return response()
            ->view('erm.rawatjalans.assets.index_js', compact('dokters', 'metodeBayar', 'role', 'defaultDokterId', 'isDokter'))
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

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

    public function sendNotifToDokter(Request $request)
    {
        if (!Auth::user()->hasRole('Perawat')) {
            return response()->json(['success' => false], 403);
        }

        $request->validate([
            'visitation_id' => 'required',
        ]);

        $visitation = Visitation::with(['pasien:id,nama', 'dokter.user:id,name'])->findOrFail($request->visitation_id);
        $dokterUser = optional($visitation->dokter)->user;

        if (!$dokterUser) {
            return response()->json([
                'success' => false,
                'message' => 'Dokter untuk kunjungan ini tidak ditemukan.',
            ], 404);
        }

        $patient = $visitation->pasien;
        $patientName = optional($patient)->nama ?: 'Pasien';
        $patientId = optional($patient)->id;
        $title = trim($patientName . ($patientId ? ' (' . $patientId . ')' : '') . ' Memasuki Ruangan');
        $message = 'Pasien ' . $patientName . ' memasuki ruangan.';
        $dokumenUrl = $visitation->status_dokumen === 'cppt'
            ? route('erm.cppt.create', $visitation->id)
            : route('erm.asesmendokter.create', $visitation->id);

        $dokterUser->notify(new PerawatToDokterNotification($message, $title, $dokumenUrl));

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim ke dokter.',
            'dokter_name' => $dokterUser->name,
        ]);
    }

    private function notificationClassForUser($user)
    {
        if (!$user) {
            return null;
        }

        if ($user->hasRole('Perawat')) {
            return DokterToPerawatNotification::class;
        }

        if ($user->hasRole('Dokter')) {
            return PerawatToDokterNotification::class;
        }

        return null;
    }

    private function notificationTitleForUser($user)
    {
        if (!$user) {
            return 'Notifikasi';
        }

        if ($user->hasRole('Perawat')) {
            return 'Notifikasi dari Dokter';
        }

        if ($user->hasRole('Dokter')) {
            return 'Notifikasi dari Perawat';
        }

        return 'Notifikasi';
    }

    /**
     * Poll for unread notifications for Perawat
     */
    public function getNotif(Request $request)
    {
        $user = Auth::user();
        $notificationClass = $this->notificationClassForUser($user);

        if (!$notificationClass) {
            return response()->json(['new' => false, 'unread_count' => 0]);
        }

        $latestUnreadQuery = $user->unreadNotifications()
            ->where('type', $notificationClass);

        if ($request->filled('since_ms')) {
            try {
                $since = Carbon::createFromTimestampMs((int) $request->input('since_ms'), config('app.timezone'));
                $latestUnreadQuery->where('created_at', '>=', $since);
            } catch (\Throwable $e) {
                // Ignore invalid client timestamps and fall back to existing behavior.
            }
        }

        $notif = $latestUnreadQuery
            ->latest()
            ->first();
        $deliveredNotificationIds = session('rawatjalan_delivered_notification_ids', []);
        if ($notif) {
            $unreadCount = $user->unreadNotifications()
                ->where('type', $notificationClass)
                ->count();

            if (in_array($notif->id, $deliveredNotificationIds, true)) {
                return response()->json([
                    'new' => false,
                    'id' => $notif->id,
                    'unread_count' => $unreadCount,
                ]);
            }

            $deliveredNotificationIds[] = $notif->id;
            session(['rawatjalan_delivered_notification_ids' => array_values(array_unique($deliveredNotificationIds))]);

            return response()->json([
                'new' => true,
                'id' => $notif->id,
                'title' => $notif->data['title'] ?? $this->notificationTitleForUser($user),
                'message' => $notif->data['message'],
                'sender' => $notif->data['sender'],
                'dokumen_url' => $notif->data['dokumen_url'] ?? null,
                'unread_count' => $unreadCount,
            ]);
        }
        $unreadCount = $user->unreadNotifications()
            ->where('type', $notificationClass)
            ->count();
        return response()->json(['new' => false, 'unread_count' => $unreadCount]);
    }

    public function markNotifRead(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'notification_id' => 'required|string',
        ]);

        $notificationClass = $this->notificationClassForUser(Auth::user());

        if (!$notificationClass) {
            return response()->json(['message' => 'Notifikasi tidak tersedia'], 403);
        }

        $notification = Auth::user()
            ->notifications()
            ->where('id', $request->notification_id)
            ->where('type', $notificationClass)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);
        }

        if (is_null($notification->read_at)) {
            Auth::user()
                ->notifications()
                ->where('id', $notification->id)
                ->update(['read_at' => now()]);
        }

        $deliveredNotificationIds = session('rawatjalan_delivered_notification_ids', []);
        if (!empty($deliveredNotificationIds)) {
            session([
                'rawatjalan_delivered_notification_ids' => array_values(array_filter($deliveredNotificationIds, function ($id) use ($notification) {
                    return $id !== $notification->id;
                })),
            ]);
        }

        $unreadCount = Auth::user()
            ->unreadNotifications()
            ->where('type', $notificationClass)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllNotifRead()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $notificationClass = $this->notificationClassForUser(Auth::user());

        if (!$notificationClass) {
            return response()->json(['message' => 'Notifikasi tidak tersedia'], 403);
        }

        $notifications = Auth::user()
            ->unreadNotifications()
            ->where('type', $notificationClass)
            ->get();

        foreach ($notifications as $notification) {
            if (is_null($notification->read_at)) {
                Auth::user()
                    ->notifications()
                    ->where('id', $notification->id)
                    ->update(['read_at' => now()]);
            }
        }

        session(['rawatjalan_delivered_notification_ids' => []]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    public function notificationUnreadCount()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $notificationClass = $this->notificationClassForUser(Auth::user());

        if (!$notificationClass) {
            return response()->json([
                'unread_count' => 0,
            ]);
        }

        $count = Auth::user()
            ->unreadNotifications()
            ->where('type', $notificationClass)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function notificationHistory()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $notificationClass = $this->notificationClassForUser(Auth::user());

        if (!$notificationClass) {
            return response()->json([
                'data' => [],
                'unread_count' => 0,
            ]);
        }

        $notifications = Auth::user()
            ->notifications()
            ->where('type', $notificationClass)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notifikasi',
                    'message' => $notification->data['message'] ?? '-',
                    'sender' => $notification->data['sender'] ?? '-',
                    'dokumen_url' => $notification->data['dokumen_url'] ?? null,
                    'created_at' => optional($notification->created_at)->format('d/m/Y H:i'),
                    'read_at' => optional($notification->read_at)->format('d/m/Y H:i'),
                    'is_read' => !is_null($notification->read_at),
                ];
            })
            ->values();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $notifications->where('is_read', false)->count(),
        ]);
    }

    public function scheduledMessages(Request $request)
    {
        $query = WaScheduledMessage::query()
            ->leftJoin('erm_pasiens as pasien', 'wa_scheduled_messages.pasien_id', '=', 'pasien.id')
            ->select([
                'wa_scheduled_messages.id',
                'wa_scheduled_messages.client_id',
                'wa_scheduled_messages.pasien_id',
                'wa_scheduled_messages.to',
                'wa_scheduled_messages.message',
                'wa_scheduled_messages.schedule_at',
                'wa_scheduled_messages.status',
                'wa_scheduled_messages.created_at',
                'pasien.nama as pasien_nama',
            ]);

        if ($request->filled('start_date')) {
            $query->whereDate('wa_scheduled_messages.schedule_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('wa_scheduled_messages.schedule_at', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->filterColumn('pasien_nama', function ($query, $keyword) {
                $query->where('pasien.nama', 'like', "%{$keyword}%");
            })
            ->editColumn('pasien_nama', function ($row) {
                return $row->pasien_nama ?: '-';
            })
            ->editColumn('schedule_at', function ($row) {
                if (empty($row->schedule_at)) {
                    return null;
                }

                return Carbon::parse($row->schedule_at)->toDateTimeString();
            })
            ->addColumn('message_preview', function ($row) {
                return \Illuminate\Support\Str::limit(trim((string) $row->message), 80);
            })
            ->toJson();
    }

    public function visitationMessages(string $visitation)
    {
        $visitationModel = Visitation::with(['pasien:id,nama'])->findOrFail($visitation);

        $messages = WaMessage::query()
            ->where('visitation_id', (string) $visitation)
            ->orderBy('created_at')
            ->get(['id', 'direction', 'from', 'to', 'body', 'message_id', 'created_at'])
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'direction' => $message->direction,
                    'from' => $message->from,
                    'to' => $message->to,
                    'body' => $message->body,
                    'message_id' => $message->message_id,
                    'created_at' => optional($message->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'visitation' => [
                'id' => (string) $visitationModel->id,
                'pasien_nama' => optional($visitationModel->pasien)->nama ?: '-',
            ],
            'messages' => $messages,
        ]);
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
                $user = Auth::user();
                $visitations = Visitation::query()
                    ->select([
                        'erm_visitations.id',
                        'erm_visitations.pasien_id',
                        'erm_visitations.metode_bayar_id',
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
                        'erm_pasiens.notes as catatan_pasien',
                        'erm_pasiens.status_pasien as status_pasien',
                        'erm_pasiens.status_akses as status_akses',
                        'erm_pasiens.status_review as status_review',

                        'mb.nama as metode_bayar_nama',
                        'u.name as dokter_user_name',
                        's.nama as spesialisasi_nama',
                    ])
                    // include merchandise count per pasien using subquery
                    ->selectRaw('(SELECT COUNT(1) FROM erm_pasien_merchandises WHERE erm_pasien_merchandises.pasien_id = erm_pasiens.id) as merchandise_count')
                    // avoid eager-load queries by using cheap correlated subqueries
                    ->selectRaw('EXISTS(SELECT 1 FROM erm_screening_batuk sb WHERE sb.visitation_id = erm_visitations.id) as has_screening_batuk')
                    ->selectRaw('EXISTS(SELECT 1 FROM wa_scheduled_messages wsm WHERE wsm.visitation_id = erm_visitations.id) as has_wa_scheduled_message')
                    ->selectRaw("(SELECT COUNT(1) FROM wa_messages wm WHERE wm.visitation_id = erm_visitations.id AND LOWER(COALESCE(wm.direction, '')) = 'in') as incoming_wa_message_count")
                    ->selectSub(
                        DB::table('erm_asesmen_penunjang as ap')
                            ->select('ap.created_at')
                            ->whereColumn('ap.visitation_id', 'erm_visitations.id')
                            ->limit(1),
                        'asesmen_penunjang_created_at'
                    )
                    ->selectSub(
                        DB::table('erm_cppt as c')
                            ->select('c.created_at')
                            ->whereColumn('c.visitation_id', 'erm_visitations.id')
                            ->limit(1),
                        'cppt_created_at'
                    )
                    ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                    ->leftJoin('erm_metode_bayar as mb', 'erm_visitations.metode_bayar_id', '=', 'mb.id')
                    ->leftJoin('erm_dokters as d', 'erm_visitations.dokter_id', '=', 'd.id')
                    ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                    ->leftJoin('erm_spesialisasis as s', 'd.spesialisasi_id', '=', 's.id')
                    ->when($user && $user->hasRole('Dokter'), function($q) {
                        // Dokter view: only show Konsultasi (jenis_kunjungan = 1)
                        $q->where('erm_visitations.jenis_kunjungan', 1);
                    }, function($q) {
                        // Non-dokter view: show Konsultasi + Produk/Obat
                        $q->whereIn('erm_visitations.jenis_kunjungan', [1, 2]);
                    })
                    ->where('erm_visitations.status_kunjungan', '!=', 7);

            if ($request->filled('klinik_id')) {
                $visitations->where('erm_visitations.klinik_id', $request->klinik_id);
            }
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
            // Use withCount (subqueries) for row-level indicators without extra eager-load queries
            $visitations->withCount([
                'labPermintaan as lab_permintaan_count',
                // Count only completed lab requests
                'labPermintaan as lab_permintaan_completed_count' => function($q){
                    $q->where('status','completed');
                },
                'riwayatTindakan as riwayat_tindakan_count',
                'suratIstirahats as surat_istirahat_count' => function ($q) {
                    $q->whereRaw('DATE(created_at) = DATE(erm_visitations.tanggal_visitation)');
                },
                'suratMondoks as surat_mondok_count' => function ($q) {
                    $q->whereRaw('DATE(created_at) = DATE(erm_visitations.tanggal_visitation)');
                }
            ]);
            return datatables()->of($visitations)
                ->filterColumn('nama_pasien', function ($query, $keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('erm_pasiens.nama', 'like', "%{$keyword}%")
                            ->orWhere('erm_pasiens.notes', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('no_rm', function ($query, $keyword) {
                    $query->where('erm_pasiens.id', 'like', "%{$keyword}%");
                })
                ->addColumn('antrian', function ($v) {
                    return '<span data-order="' . intval($v->no_antrian) . '">' . $v->no_antrian . '</span>';
                })
                ->addColumn('no_rm', function($v) { return $v->no_rm ?? '-'; })
                ->addColumn('nama_pasien', function ($v) {
                    $nama = $v->nama_pasien ?? '-';
                    $icons = '';
                    
                    // Check patient age and render as a small badge instead of an icon
                    if ($v->tanggal_lahir) {
                        $birthDate = new \DateTime($v->tanggal_lahir);
                        $today = new \DateTime();
                        $age = $today->diff($birthDate)->y;
                        // Use a badge for age; pink for <17, neutral for others
                        if ($age < 17) {
                            $icons .= ' <small class="badge" style="background-color:#ff69b4;color:#fff;margin-right:5px;" title="Pasien di bawah 17 tahun">' . $age . ' th</small>';
                        } else {
                            $icons .= ' <small class="badge badge-secondary" style="margin-right:5px;">' . $age . ' th</small>';
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
                    // Show review status as a badge if pasien has been reviewed (status_review === 'sudah')
                    if (isset($v->status_review) && strtolower($v->status_review) === 'sudah') {
                        $icons .= ' <small class="badge badge-success" style="margin-right:5px;" title="Sudah Review">Sudah Review</small>';
                    }
                    // Return only the patient name here; badges (age, review, merch, RM) will
                    // be rendered client-side so they appear under the name consistently.
                    return $nama;
                })
                ->addColumn('tanggal', function ($v) {
                    return \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                })
                ->addColumn('metode_bayar', function($v) { return $v->metode_bayar_nama ?? '-'; })
                ->addColumn('spesialisasi', function ($v) {
                    return $v->spesialisasi_nama ?? '-';
                })
                ->addColumn('dokter_nama', function ($v) {
                    return $v->dokter_user_name ?? '-';
                })
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $actionButtons = [];
                    $whatsAppButton = '';
                    if ($user->hasRole('Perawat')) {
                        $visitationId = (string) $v->id;
                        if (!empty($v->has_screening_batuk)) {
                            $actionButtons[] = '<a href="' . route('erm.asesmenperawat.create', $v->id) . '" class="btn btn-sm btn-primary" style="font-weight:bold;" title="Dokumen"><i class="fas fa-file-alt mr-1"></i>Dokumen</a>';
                            $actionButtons[] = '<button class="btn btn-sm btn-warning view-screening-btn" style="font-weight:bold;" title="Screening Batuk" data-visitation-id="' . $visitationId . '"><i class="fas fa-lungs"></i></button>';
                        } else {
                            $actionButtons[] = '<button class="btn btn-sm btn-primary screening-btn" style="font-weight:bold;" title="Dokumen" data-visitation-id="' . $visitationId . '"><i class="fas fa-file-alt mr-1"></i>Dokumen</button>';
                        }
                        $actionButtons[] = '<button class="btn btn-sm btn-info btn-notify-dokter-patient-enter" style="font-weight:bold;" title="Pasien Memasuki Ruangan" data-visitation-id="' . $visitationId . '" data-pasien-nama="' . e($v->nama_pasien ?? 'Pasien') . '"><i class="fas fa-door-open"></i></button>';
                    } elseif ($user->hasRole('Dokter')) {
                        if ($v->status_dokumen === 'asesmen') {
                            $url = route('erm.asesmendokter.create', $v->id);
                            $actionButtons[] = '<a href="' . $url . '" class="btn btn-sm btn-primary" style="font-weight:bold;" title="Asesmen"><i class="fas fa-user-md mr-1"></i>Asesmen</a>';
                        } elseif ($v->status_dokumen === 'cppt') {
                            $url = route('erm.cppt.create', $v->id);
                            $actionButtons[] = '<a href="' . $url . '" class="btn btn-sm btn-success" style="font-weight:bold;" title="CPPT"><i class="fas fa-notes-medical mr-1"></i>CPPT</a>';
                        }
                    }
                    if ($user->hasRole('Pendaftaran') || $user->hasRole('Perawat')) {
                        $incomingWaCount = intval($v->incoming_wa_message_count ?? 0);
                        if (!empty($v->has_wa_scheduled_message) || $incomingWaCount > 0) {
                            $badgeHtml = $incomingWaCount > 0
                                ? '<span class="position-absolute badge badge-danger" style="top:-6px; right:-6px; min-width:18px; height:18px; line-height:18px; padding:0 4px; font-size:10px; border-radius:999px;">' . $incomingWaCount . '</span>'
                                : '';
                            $whatsAppButton = '<button class="btn btn-sm btn-success open-visitation-chat position-relative" style="font-weight:bold; overflow: visible;" data-visitation-id="' . e($v->id) . '" data-pasien-nama="' . e($v->nama_pasien ?? '-') . '" title="Riwayat WhatsApp"><i class="fab fa-whatsapp"></i>' . $badgeHtml . '</button>';
                        }
                        $waktuKunjungan = $v->waktu_kunjungan ?? '';
                        // Ensure we always emit a valid JS argument for no_antrian (use null literal when empty)
                        $antrianJs = json_encode($v->no_antrian);
                        $actionButtons[] = '<button class="btn btn-sm btn-secondary" style="font-weight:bold;" onclick="editAntrian(\'' . $v->id . '\', ' . $antrianJs . ', \'' . htmlspecialchars($waktuKunjungan, ENT_QUOTES, 'UTF-8') . '\')" title="Edit Antrian"><i class=\'fas fa-edit\'></i></button>';
                        }

                        $buttonHtml = '';
                        if (!empty($actionButtons) || $whatsAppButton !== '') {
                            $buttonHtml .= '<div class="d-inline-flex align-items-center">';
                            if (!empty($actionButtons)) {
                                $buttonHtml .= '<div class="btn-group btn-group-sm" role="group">' . implode('', $actionButtons) . '</div>';
                            }
                            if ($whatsAppButton !== '') {
                                $buttonHtml .= '<div class="ml-1">' . $whatsAppButton . '</div>';
                            }
                            $buttonHtml .= '</div>';
                        }

                        return $buttonHtml;
                    })
                ->addColumn('waktu_kunjungan', function ($v) {
                    return $v->waktu_kunjungan ? substr($v->waktu_kunjungan, 0, 5) : '-';
                })
                // Reduce JSON payload: keep only fields actually used by DataTables JS.
                // Values still used inside the generated HTML (e.g. WA button) are embedded in `dokumen`.
                ->removeColumn('telepon_pasien')
                ->removeColumn('gender')
                ->removeColumn('status_dokumen')
                ->removeColumn('metode_bayar_nama')
                ->removeColumn('dokter_user_name')
                ->removeColumn('spesialisasi_nama')
                ->removeColumn('has_screening_batuk')
                ->removeColumn('asesmen_penunjang_created_at')
                ->removeColumn('cppt_created_at')
                ->rawColumns(['antrian', 'nama_pasien', 'dokumen'])
                ->make(true);
            } catch (\Exception $e) {
                Log::error('RawatJalanController@index AJAX error: ' . $e->getMessage(), ['exception' => $e]);
                return response()->json(['error' => 'Internal Server Error'], 500);
            }
        }

        // Defer statistics: keep initial page render light.
        // The stats cards will be populated via AJAX (updateStats() -> erm.rawatjalans.stats) after first paint.
        $stats = [
            'total' => '...',
            'tidak_datang' => '...',
            'belum_diperiksa' => '...',
            'sudah_diperiksa' => '...',
            'dibatalkan' => '...',
            'rujuk' => '...',
            'lab_permintaan' => '...',
        ];

        // Cache dropdown lists to make page renders lighter
        $dokters = Cache::remember('erm_dokters_list', 300, function() {
            return Dokter::select('id', 'user_id', 'spesialisasi_id')
                ->with(['user:id,name', 'spesialisasi:id,nama'])
                ->get();
        });

        $metodeBayar = Cache::remember('erm_metode_bayar', 300, function() {
            return MetodeBayar::select('id', 'nama')->get();
        });

        $kliniks = Cache::remember('erm_kliniks', 300, function() {
            return Klinik::select('id', 'nama')->get();
        });
        $role = Auth::user()->getRoleNames()->first();
        $isDokter = Auth::user()->hasRole('Dokter');
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
        return view('erm.rawatjalans.index', compact('dokters', 'metodeBayar', 'role', 'kliniks', 'stats', 'defaultDokterId', 'isDokter'));
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

        return Cache::remember($cacheKey, 10, function() use ($today, $dokter) {
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

    $stats = Cache::remember($cacheKey, 10, function() use ($start, $end, $dokterFilter, $klinikFilter) {
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
     * Print / render surat rujuk (consultation letter) view for given rujuk id.
     * Opens a printable HTML page which user can print to PDF via browser.
     */
    public function printRujukSurat($id)
    {
        try {
            $rujuk = Rujuk::with(['pasien', 'dokterPengirim.user', 'dokterTujuan.user', 'visitation'])->findOrFail($id);

            // Calculate age string if tanggal_lahir available
            $age = null;
            if ($rujuk->pasien && $rujuk->pasien->tanggal_lahir) {
                $birth = \Carbon\Carbon::parse($rujuk->pasien->tanggal_lahir);
                $age = $birth->age;
            }

            // Generate PDF and stream to browser
            try {
                $pdf = Pdf::loadView('erm.rujuk.surat_pdf', compact('rujuk', 'age'))
                    ->setPaper('a4', 'portrait');
                // Stream the PDF (opens in browser). Use download() to force download.
                return $pdf->stream('surat_rujuk_' . $rujuk->id . '.pdf');
            } catch (\Exception $e) {
                Log::error('PDF generation failed: ' . $e->getMessage(), ['id' => $id]);
                // Fallback to HTML view if PDF generation fails
                return view('erm.rujuk.surat', compact('rujuk', 'age'));
            }
        } catch (\Exception $e) {
            Log::error('printRujukSurat error: ' . $e->getMessage(), ['id' => $id]);
            abort(404, 'Surat rujuk tidak ditemukan');
        }
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

        // Duplicate rule: for konsultasi (jenis 1), only ONE active visit per pasien per tanggal PER DOKTER.
        // (If dokternya berbeda, boleh didaftarkan lagi.)
        $exists = Visitation::where('pasien_id', $request->pasien_id)
            ->whereDate('tanggal_visitation', $request->tanggal_visitation)
            ->where('dokter_id', $request->dokter_id)
            ->where('status_kunjungan', '!=', 7)
            ->where(function ($q) {
                $q->where('jenis_kunjungan', 1)
                  ->orWhereNull('jenis_kunjungan');
            })
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien sudah didaftarkan untuk kunjungan hari ini dengan dokter yang sama.'
            ], 422);
        }

        // update kunjungan lama jadi progress 7
        if ($request->has('visitation_id')) {
            Visitation::where('id', $request->visitation_id)->update([
                'status_kunjungan' => 7
            ]);
        }

        // buat kunjungan baru
        $visitation = Visitation::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'waktu_kunjungan' => $request->waktu_kunjungan, // save waktu_kunjungan
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id ?? 1,
            'status_kunjungan' => 0,
            'jenis_kunjungan' => 1,
        ]);

        try {
            app(VisitationWhatsAppScheduler::class)->queueForVisitation($visitation);
        } catch (\Exception $e) {
            Log::error('Failed to queue visitation WhatsApp from RawatJalanController', [
                'visitation_id' => $visitation->id,
                'error' => $e->getMessage(),
            ]);
        }

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

    /**
     * Permanently delete a visitation that is in 'dibatalkan' status (7).
     * Only users with Admin or Pendaftaran role may perform this.
     */
    public function forceDestroy(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
        ]);

        $user = Auth::user();
        // Allow Admin, Pendaftaran or Perawat to perform force delete
        if (!$user || (! $user->hasRole('Admin') && ! $user->hasRole('Pendaftaran') && ! $user->hasRole('Perawat'))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $visitation = Visitation::findOrFail($request->visitation_id);

        if ($visitation->status_kunjungan != 7) {
            return response()->json(['success' => false, 'message' => 'Visitation is not in dibatalkan status'], 400);
        }

        DB::beginTransaction();
        try {
            // Delete related screening, lab requests, rujuk and other related records if present
            if ($visitation->screeningBatuk) {
                $visitation->screeningBatuk()->delete();
            }
            if ($visitation->labPermintaan()->exists()) {
                $visitation->labPermintaan()->delete();
            }
            if ($visitation->resepDokter()->exists()) {
                $visitation->resepDokter()->delete();
            }
            if ($visitation->resepFarmasi()->exists()) {
                $visitation->resepFarmasi()->delete();
            }
            if ($visitation->cppt()->exists()) {
                $visitation->cppt()->delete();
            }
            // Any rujuk records referencing this visitation
            Rujuk::where('visitation_id', $visitation->id)->delete();

            // Finally delete the visitation itself
            $visitation->delete();

            DB::commit();
            Log::info('Visitation force deleted', ['visitation_id' => $request->visitation_id, 'user_id' => $user->id]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to force delete visitation: ' . $e->getMessage(), ['visitation_id' => $request->visitation_id, 'user_id' => $user->id]);
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
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

    /**
     * Update metode bayar for a visitation (AJAX)
     */
    public function updateMetodeBayar(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
        ]);

        try {
            $visitation = Visitation::findOrFail($request->visitation_id);
            $visitation->metode_bayar_id = $request->metode_bayar_id;
            $visitation->save();

            $metodeName = null;
            if ($visitation->metodeBayar) {
                $metodeName = $visitation->metodeBayar->nama;
            } else {
                $m = MetodeBayar::find($request->metode_bayar_id);
                $metodeName = $m ? $m->nama : null;
            }

            // Optionally clear related caches
            Cache::forget('visitation_stats_' . now()->format('Y-m-d') . '_dok_all');

            return response()->json(['success' => true, 'metode' => $metodeName]);
        } catch (\Exception $e) {
            Log::error('updateMetodeBayar error: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
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

    /**
     * Return lab permintaan (tests) for a single visitation id, flattened per test with durations.
     */
    public function labPermintaanByVisitation($visitationId)
    {
        try {
            $vid = (string) $visitationId; // visitation_id stored as string in migration
            $labs = LabPermintaan::with(['labTest:id,nama','visitation:id,pasien_id,no_antrian','visitation.pasien:id,nama'])
                ->where('visitation_id', $vid)
                ->orderBy('created_at','asc')
                ->get();
            Log::info('labPermintaanByVisitation fetch', ['visitation_id'=>$vid, 'count'=>$labs->count()]);
            $data = $labs->map(function($l){
                $processSeconds = null; $processMinutes = null; $processHuman = null;
                if ($l->processed_at && $l->completed_at) {
                    $processSeconds = $l->completed_at->diffInSeconds($l->processed_at);
                    $processMinutes = (int) floor($processSeconds / 60);
                    $remaining = $processSeconds % 60;
                    $processHuman = $processMinutes > 0 ? ($processMinutes.'m'.($remaining? ' '.$remaining.'s':'')) : ($remaining.'s');
                }
                return [
                    'id' => $l->id,
                    'lab_test' => $l->labTest?->nama ?? '-',
                    'status' => $l->status,
                    'requested_at' => optional($l->requested_at)->format('Y-m-d H:i:s'),
                    'processed_at' => optional($l->processed_at)->format('Y-m-d H:i:s'),
                    'completed_at' => optional($l->completed_at)->format('Y-m-d H:i:s'),
                    'process_time_seconds' => $processSeconds,
                    'process_time_human' => $processHuman,
                ];
            })->values();
            return response()->json([
                'data' => $data,
                'meta' => [
                    'visitation_id' => $vid,
                    'count' => $data->count()
                ]
            ]);
        } catch(\Exception $e) {
            Log::error('labPermintaanByVisitation error', ['msg'=>$e->getMessage(),'visitation_id'=>$visitationId]);
            return response()->json(['data'=>[],'error'=>'Internal Server Error','meta'=>['visitation_id'=>(string)$visitationId]],500);
        }
    }

}
