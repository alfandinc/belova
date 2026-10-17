<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\BirthdayGreeting;
use App\Models\ERM\Klinik;
use App\Models\ERM\Pasien;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\Visitation;
use App\Models\Marketing\CatatanKeluhan;
use App\Models\Marketing\FollowUp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Marketing patient data. The marketing dashboard and analytics pages (revenue, patients,
 * services, products, clinic comparison) were removed.
 * The "Pasien & Follow Up" page (tabs: Pasien, Follow Up, Ulang Tahun) is served from pasienData().
 */
class MarketingController extends Controller
{
    public function pasienData(Request $request)
    {
        if ($request->ajax()) {
            $lastVisit = fn (string $column) => Visitation::select($column)
                ->whereColumn('pasien_id', 'erm_pasiens.id')
                ->orderByDesc('tanggal_visitation')
                ->limit(1);

            $data = Pasien::select('id', 'nama', 'identity_document', DB::raw('identity_number as nik'), 'tanggal_lahir', 'gender', 'agama', 'marital_status', 'pendidikan', 'pekerjaan', 'gol_darah', 'notes', 'alamat', 'no_hp', 'no_hp2', 'email', 'instagram', 'status_pasien', 'status_akses', 'status_review')
                ->addSelect([
                    'last_visitation_date' => $lastVisit('tanggal_visitation'),
                    'last_visit_klinik_id' => $lastVisit('klinik_id'),
                    'has_recent_followup' => FollowUp::selectRaw('1')
                        ->whereColumn('pasien_id', 'erm_pasiens.id')
                        ->where('created_at', '>=', Carbon::now()->subWeek())
                        ->limit(1),
                    'open_keluhan_count' => CatatanKeluhan::selectRaw('COUNT(*)')
                        ->whereColumn('pasien_id', 'erm_pasiens.id')
                        ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', CatatanKeluhan::CLOSED_STATUSES)),
                ]);

            // Newest visit first unless the user sorts by a column
            if (!$request->filled('order')) {
                $data->orderByRaw('ISNULL(last_visitation_date), last_visitation_date DESC');
            }

            // Last visit older than the chosen period
            $lastVisitBefore = [
                'gt1w' => Carbon::now()->subWeek(),
                'gt1m' => Carbon::now()->subMonth(),
                'gt3m' => Carbon::now()->subMonths(3),
                'gt6m' => Carbon::now()->subMonths(6),
                'gt1y' => Carbon::now()->subYear(),
            ][$request->input('last_visit')] ?? null;
            if ($lastVisitBefore) {
                $data->having('last_visitation_date', '<', $lastVisitBefore->toDateString());
            }

            // Clinic of the last visit
            if ($request->filled('last_visit_klinik') && $request->last_visit_klinik !== 'all') {
                $data->having('last_visit_klinik_id', $request->last_visit_klinik);
            }

            $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
            $formatDate = fn (Carbon $date) => $date->day . ' ' . $bulan[$date->month] . ' ' . $date->year;

            return datatables()
                ->of($data)
                ->addColumn('gender_text', fn ($row) => in_array($row->gender, ['Laki-laki', 'Perempuan'], true) ? $row->gender : '-')
                ->addColumn('followed_up_7d', fn ($row) => (bool) $row->has_recent_followup)
                ->addColumn('is_birthday_today', function ($row) {
                    if (!$row->tanggal_lahir) return false;
                    $born = Carbon::parse($row->tanggal_lahir);
                    return $born->month === now()->month && $born->day === now()->day;
                })
                ->editColumn('tanggal_lahir', function ($row) use ($formatDate) {
                    if (!$row->tanggal_lahir) return '-';
                    $born = Carbon::parse($row->tanggal_lahir);
                    return $formatDate($born) . ' <span class="text-muted">(' . $born->age . ' th)</span>';
                })
                ->addColumn('kunjungan_terakhir', function ($row) use ($formatDate) {
                    if (!$row->last_visitation_date) return '<span class="text-muted">Belum pernah</span>';
                    $visit = Carbon::parse($row->last_visitation_date)->startOfDay();
                    $diff = $visit->diff(now()->startOfDay());
                    $ago = array_filter([
                        $diff->y ? $diff->y . ' th' : null,
                        $diff->m ? $diff->m . ' bln' : null,
                        $diff->d ? $diff->d . ' hr' : null,
                    ]);
                    return $formatDate($visit) . '<div class="small text-muted">' . ($ago ? implode(' ', $ago) . ' lalu' : 'hari ini') . '</div>';
                })
                ->rawColumns(['tanggal_lahir', 'kunjungan_terakhir'])
                ->make(true);
        }
        
        return self::crmPasienView($request->query('tab'));
    }

    /** The merged Pasien / Follow Up / Ulang Tahun page, opened on $tab. */
    public static function crmPasienView(?string $tab)
    {
        $today = Carbon::today();
        $salesEmployee = auth()->user()->employee ?? null;

        return view('marketing.crm-pasien.index', [
            'activeTab' => in_array($tab, ['pasien', 'followup', 'ulang-tahun'], true) ? $tab : 'pasien',
            'kliniks' => Klinik::orderBy('nama')->get(['id', 'nama', 'color']),
            'today' => $today->format('Y-m-d'),
            // Tab badge: birthdays today not yet greeted
            'birthdayTodayCount' => Pasien::whereMonth('tanggal_lahir', $today->month)
                ->whereDay('tanggal_lahir', $today->day)
                ->whereNotIn('id', BirthdayGreeting::where('greeting_year', $today->year)->select('pasien_id'))
                ->count(),
            // Default Sales of a new follow up: the logged in user's employee record
            'defaultSales' => $salesEmployee ? ['id' => $salesEmployee->id, 'nama' => $salesEmployee->nama] : null,
        ]);
    }

    /**
     * AJAX: Get riwayat resep dokter & tindakan grouped by visitation for a pasien
     */
    public function riwayatRM($pasienId)
    {
        $visitations = Visitation::with(['dokter.user'])
            ->where('pasien_id', $pasienId)
            ->orderByDesc('tanggal_visitation')
            ->get();

        $visitationIds = $visitations->pluck('id');

        $resepsByVisitation = ResepFarmasi::with(['obat', 'wadah'])
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('visitation_id');

        // stored paket of each racikan, or the paket with exactly its isi (ResepFarmasi::paket_racikan_name)
        $racikanPaketNames = [];
        foreach ($resepsByVisitation as $visitationId => $reseps) {
            foreach ($reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke') as $racikanKe => $items) {
                $paketName = $items->first()->paket_racikan_name;
                if ($paketName) {
                    $racikanPaketNames[$visitationId][$racikanKe] = $paketName;
                }
            }
        }

        $tindakanByVisitation = \App\Models\ERM\RiwayatTindakan::with('tindakan')
            ->whereIn('visitation_id', $visitationIds)
            ->orderBy('tanggal_tindakan')
            ->orderBy('id')
            ->get()
            ->groupBy('visitation_id');

        return view('marketing.pasien-data.riwayat-rm', [
            'visitations' => $visitations,
            'resepsByVisitation' => $resepsByVisitation,
            'racikanPaketNames' => $racikanPaketNames,
            'tindakanByVisitation' => $tindakanByVisitation,
        ]);
    }
}
