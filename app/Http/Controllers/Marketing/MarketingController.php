<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

use App\Models\ERM\Klinik;
use App\Models\ERM\Dokter;
use App\Models\ERM\Obat;
use App\Models\ERM\Pasien;
use App\Models\ERM\Tindakan;
use App\Models\ERM\Visitation;
use App\Models\ERM\PaketTindakan;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\LabPermintaan;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarketingController extends Controller
{
    public function dashboard()
    {
        $clinics = Klinik::all();
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        // Quick stats for dashboard
        $stats = $this->getDashboardStats();
        
        return view('marketing.dashboard', compact('clinics', 'currentYear', 'currentMonth', 'stats'));
    }

    /**
     * Get comprehensive dashboard statistics
     */
    private function getDashboardStats()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();
        
        return [
            'patients' => [
                'total' => Pasien::count(),
                'new_today' => Pasien::whereDate('created_at', $today)->count(),
                'new_this_month' => Pasien::whereDate('created_at', '>=', $thisMonth)->count(),
                'active_this_year' => Pasien::whereHas('visitations', function($q) use ($thisYear) {
                    $q->where('tanggal_visitation', '>=', $thisYear);
                })->count()
            ],
            'revenue' => [
                'today' => Invoice::whereHas('visitation', function($q) use ($today) { 
                    $q->whereDate('tanggal_visitation', $today); 
                })->where('amount_paid', '>', 0)->sum('total_amount'),
                'this_month' => Invoice::whereHas('visitation', function($q) use ($thisMonth) { 
                    $q->whereDate('tanggal_visitation', '>=', $thisMonth); 
                })->where('amount_paid', '>', 0)->sum('total_amount'),
                'this_year' => Invoice::whereHas('visitation', function($q) use ($thisYear) { 
                    $q->whereDate('tanggal_visitation', '>=', $thisYear); 
                })->where('amount_paid', '>', 0)->sum('total_amount'),
                'average_per_visit' => $this->getAverageRevenuePerVisit()
            ],
            'visits' => [
                'today' => Visitation::whereDate('tanggal_visitation', $today)->count(),
                'this_month' => Visitation::whereDate('tanggal_visitation', '>=', $thisMonth)->count(),
                'this_year' => Visitation::whereDate('tanggal_visitation', '>=', $thisYear)->count(),
            ],
            'treatments' => [
                'most_popular' => $this->getMostPopularTreatment(),
                'total_performed' => InvoiceItem::where('billable_type', 'App\\Models\\ERM\\Tindakan')->count(),
            ]
        ];
    }

    private function getAverageRevenuePerVisit()
    {
        $totalRevenue = Invoice::whereHas('visitation')->where('amount_paid', '>', 0)->sum('total_amount');
        $totalVisits = Visitation::whereHas('invoice', function($q) {
            $q->where('amount_paid', '>', 0);
        })->count();
        return $totalVisits > 0 ? round($totalRevenue / $totalVisits, 0) : 0;
    }

    private function getMostPopularTreatment()
    {
        $treatment = InvoiceItem::where('billable_type', 'App\\Models\\ERM\\Tindakan')
            ->select('billable_id', DB::raw('COUNT(*) as count'))
            ->groupBy('billable_id')
            ->orderBy('count', 'desc')
            ->first();
            
        if ($treatment) {
            $tindakan = Tindakan::find($treatment->billable_id);
            return $tindakan ? $tindakan->nama : 'Unknown';
        }
        
        return 'No data';
    }

    public function pasienData(Request $request)
    {
        if ($request->ajax()) {
            $data = Pasien::select('id', 'nama', 'nik', 'tanggal_lahir', 'gender', 'agama', 'marital_status', 'pendidikan', 'pekerjaan', 'gol_darah', 'notes', 'alamat', 'no_hp', 'no_hp2', 'email', 'instagram')
                ->addSelect([
                    'last_visitation_date' => Visitation::select('tanggal_visitation')
                        ->whereColumn('pasien_id', 'erm_pasiens.id')
                        ->orderByDesc('tanggal_visitation')
                        ->limit(1)
                ])
                ->orderByRaw('ISNULL(last_visitation_date), last_visitation_date DESC');
            
            // Apply area filter if provided
            if ($request->has('area') && $request->area != 'all') {
                $area = $request->area;
                $data = $data->where('alamat', 'like', "%$area%");
            }

            // Filter by last visit range if provided (use last_visitation_date, not any visitation)
            if ($request->has('last_visit') && $request->last_visit != 'all') {
                $now = Carbon::now();
                switch ($request->last_visit) {
                    case 'gt1w':
                        $date = $now->copy()->subWeek();
                        $data = $data->having('last_visitation_date', '<', $date);
                        break;
                    case 'gt1m':
                        $date = $now->copy()->subMonth();
                        $data = $data->having('last_visitation_date', '<', $date);
                        break;
                    case 'gt3m':
                        $date = $now->copy()->subMonths(3);
                        $data = $data->having('last_visitation_date', '<', $date);
                        break;
                    case 'gt6m':
                        $date = $now->copy()->subMonths(6);
                        $data = $data->having('last_visitation_date', '<', $date);
                        break;
                    case 'gt1y':
                        $date = $now->copy()->subYear();
                        $data = $data->having('last_visitation_date', '<', $date);
                        break;
                }
            }
            
            // Apply last_visit_klinik filter after all Eloquent filters
            if ($request->has('last_visit_klinik') && $request->last_visit_klinik != 'all') {
                $klinikId = $request->last_visit_klinik;
                $data = $data->whereHas('visitations', function($q) use ($klinikId) {
                    $q->where('klinik_id', $klinikId);
                });
            }
            return datatables()
                ->of($data)
                ->addColumn('gender_text', function($row) {
                    return $row->gender == 'Laki-laki' ? 'Laki-laki' : ($row->gender == 'Perempuan' ? 'Perempuan' : '-');
                })
                ->addColumn('tanggal_lahir', function($row) {
                    if (!$row->tanggal_lahir) return '-';
                    $carbon = Carbon::parse($row->tanggal_lahir);
                    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    $day = $carbon->day;
                    $month = $bulan[$carbon->month];
                    $year = $carbon->year;
                    $age = $carbon->age;
                    return "$day $month $year (<b>{$age} th</b>)";
                })
                ->addColumn('no_hp', function($row) {
                    return $row->no_hp ?: '-';
                })
                ->addColumn('kunjungan_terakhir', function($row) {
                    $lastVisit = $row->visitations()->orderByDesc('tanggal_visitation')->first();
                    if ($lastVisit && $lastVisit->tanggal_visitation) {
                        $carbon = Carbon::parse($lastVisit->tanggal_visitation);
                        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                        $day = $carbon->day;
                        $month = $bulan[$carbon->month];
                        $year = $carbon->year;
                        $dateStr = "$day $month $year";
                        $now = Carbon::now();
                        $diff = $carbon->diff($now);
                        $diffStr = [];
                        if ($diff->y > 0) $diffStr[] = $diff->y . ' th';
                        if ($diff->m > 0) $diffStr[] = $diff->m . ' bln';
                        if ($diff->d > 0) $diffStr[] = $diff->d . ' hr';
                        $diffText = $diffStr ? ' (<b>' . implode(' ', $diffStr) . '</b>)' : '';
                        return $dateStr . $diffText;
                    }
                    return '-';
                })
                ->rawColumns(['gender_text', 'area', 'tanggal_lahir', 'no_hp', 'kunjungan_terakhir'])
                ->make(true);
        }
        
        return view('marketing.pasien-data.index');
    }
    
    /**
     * Calculate address statistics for the areas
     * 
     * @param int|null $clinicId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    private function getAddressStatistics($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        $areas = ['Laweyan', 'Banjarsari', 'Serengan', 'Pasar Kliwon', 'Jebres', 'Sukoharjo', 'Wonogiri', 'Karanganyar'];
        $stats = [];
        
        // Base query
        $query = Pasien::query();
        
        // Apply filters if provided
        if ($clinicId || $year || $month || $startDate || $endDate) {
            $query = $query->whereHas('visitations', function($q) use ($clinicId, $year, $month, $startDate, $endDate) {
                if ($clinicId) $q->where('klinik_id', $clinicId);
                if ($startDate && $endDate) {
                    $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                } else {
                    if ($year) $q->whereYear('tanggal_visitation', $year);
                    if ($month) $q->whereMonth('tanggal_visitation', $month);
                }
            });
        }
        
        $totalPatients = $query->count();
        
        foreach($areas as $area) {
            $areaQuery = clone $query;
            $count = $areaQuery->where('alamat', 'like', "%$area%")->count();
            $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 1) : 0;
            $stats[$area] = [
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        
        // Count others (patients that don't match any of the areas)
        $matchedAreas = 0;
        foreach($stats as $data) {
            $matchedAreas += $data['count'];
        }
        $otherCount = $totalPatients - $matchedAreas;
        $otherPercentage = $totalPatients > 0 ? round(($otherCount / $totalPatients) * 100, 1) : 0;
        
        $stats['Lainnya'] = [
            'count' => $otherCount,
            'percentage' => $otherPercentage
        ];
        
        return $stats;
    }

    public function revenue(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');

        return view('marketing.revenue', compact('year', 'month'));
    }

    // AJAX endpoints for revenue analytics
    public function getRevenueData(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $clinicId = $request->input('clinic_id');

            // If no date range provided, default to current year
            if (!$startDate || !$endDate) {
                $year = $year ?: date('Y');
            }

            $data = [
                'monthlyRevenue' => $this->getMonthlyRevenue($year, $startDate, $endDate, $clinicId),
                'doctorRevenue' => $this->getDoctorRevenue($year, $startDate, $endDate, $clinicId),
                'topPatients' => $this->getProfitablePatients($year, $startDate, $endDate, $clinicId),
                'treatmentRevenue' => $this->getRevenueByTreatmentCategory($year, $startDate, $endDate, $clinicId),
                'paymentMethodAnalysis' => $this->getPaymentMethodAnalysis($year, $startDate, $endDate, $clinicId),
                'revenueGrowth' => $this->getRevenueGrowthComparison($year, $startDate, $endDate, $clinicId),
                'dailyRevenue' => $this->getDailyRevenueTrends($year, $month ?: date('m'), $startDate, $endDate, $clinicId)
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('AJAX Revenue analytics error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get clinics for filter dropdown
    public function getClinics()
    {
        try {
            $clinics = Klinik::orderBy('nama')->get();
            
            return response()->json([
                'success' => true,
                'data' => $clinics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching clinics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Get riwayat resep dokter & tindakan grouped by visitation for a pasien
     */
    public function riwayatRM($pasienId)
    {
        // Get all visitations for this pasien, newest first
        $visitations = \App\Models\ERM\Visitation::where('pasien_id', $pasienId)
            ->orderByDesc('tanggal_visitation')
            ->get();

        $result = [];
        foreach ($visitations as $visit) {
            // Resep farmasi for this visitation
            $resep = \App\Models\ERM\ResepFarmasi::where('visitation_id', $visit->id)
                ->with('obat')
                ->get()
                ->map(function($r) {
                    return [
                        'id' => $r->id,
                        'obat_nama' => $r->obat ? $r->obat->nama : '-',
                        'jumlah' => $r->jumlah,
                        'dosis' => $r->dosis,
                        'bungkus' => $r->bungkus,
                        'racikan_ke' => $r->racikan_ke,
                        'aturan_pakai' => $r->aturan_pakai,
                        'wadah' => $r->wadah ? $r->wadah->nama : null,
                    ];
                });
            // Riwayat tindakan for this visitation
            $tindakan = \App\Models\ERM\RiwayatTindakan::where('visitation_id', $visit->id)
                ->with('tindakan')
                ->get()
                ->map(function($t) {
                    return [
                        'tindakan_nama' => $t->tindakan ? $t->tindakan->nama : '-',
                        'tanggal_tindakan' => $t->tanggal_tindakan ? $t->tanggal_tindakan->format('Y-m-d') : '-',
                    ];
                });
            // Get dokter name (from user if available)
            $dokterName = '-';
            if ($visit->dokter) {
                $dokterName = $visit->dokter->user ? $visit->dokter->user->name : ($visit->dokter->nama ?? '-');
            }
            $result[] = [
                'visitation_info' => ($visit->tanggal_visitation ? $visit->tanggal_visitation : $visit->id),
                'dokter_nama' => $dokterName,
                'resep_dokter' => $resep,
                'riwayat_tindakan' => $tindakan,
            ];
        }
        return response()->json($result);
    }

    public function patients(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');

        return view('marketing.patients', compact('year', 'month'));
    }

    public function services(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $period = $request->input('period', 'year');
        $month = $request->input('month');
        $clinicId = $request->input('clinic_id');

        // 1. Popular Treatments
        $popularTreatments = $this->getPopularTreatments($period, $clinicId, $year, $month);

        // 2. Treatment Package Performance
        $packagePerformance = $this->getPackagePerformance($period, $clinicId, $year, $month);

        // 3. Visitation Trends (monthly)
        $visitationTrends = $this->getVisitationTrends($year, $clinicId, $month);

        // 4. Doctor Performance Analysis
        $doctorPerformance = $this->getDoctorPerformanceAnalysis($year, $clinicId);

        // 5. Treatment Efficiency Analysis
        $treatmentEfficiency = $this->getTreatmentEfficiencyAnalysis($year, $clinicId);

        // 6. Service Satisfaction Trends
        $satisfactionTrends = $this->getServiceSatisfactionTrends($year, $clinicId);

        $clinics = Klinik::all();

        return view('marketing.services', compact(
            'popularTreatments',
            'packagePerformance',
            'visitationTrends',
            'doctorPerformance',
            'treatmentEfficiency',
            'satisfactionTrends',
            'clinics',
            'year',
            'period',
            'month',
            'clinicId'
        ));
    }

    public function products(Request $request)
    {
        $clinics = Klinik::all();
        
        // Get available medication categories
        $categories = Obat::withInactive()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori')
            ->sort()
            ->values();

        return view('marketing.products', compact('clinics', 'categories'));
    }

    public function clinicComparison(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // 1. Revenue Comparison
        $revenueComparison = $this->getClinicRevenueComparison($year);

        // 2. Patient Count Comparison
        $patientComparison = $this->getClinicPatientComparison($year);

        // 3. Treatment Count Comparison
        $treatmentComparison = $this->getClinicTreatmentComparison($year);

        // 4. Average Revenue per Patient
        $avgRevenuePerPatient = $this->getAvgRevenuePerPatient($year);

        return view('marketing.clinic-comparison', compact(
            'revenueComparison',
            'patientComparison',
            'treatmentComparison',
            'avgRevenuePerPatient',
            'year'
        ));
    }

    // HELPER METHODS FOR DATA RETRIEVAL

    private function getMonthlyRevenue($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoices.amount_paid', '>', 0);

        // Apply date range or year filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        // Apply clinic filter
        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            DB::raw('MONTH(erm_visitations.tanggal_visitation) as month'),
            DB::raw('SUM(finance_invoices.total_amount) as revenue')
        )
            ->groupBy(DB::raw('MONTH(erm_visitations.tanggal_visitation)'))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $seriesData = array_fill(0, 12, 0); // Initialize with zeros

        foreach ($data as $item) {
            $seriesData[$item->month - 1] = floatval($item->revenue);
        }

        return [
            'labels' => $months,
            'series' => $seriesData
        ];
    }

    private function getDoctorRevenue($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_dokters', 'erm_visitations.dokter_id', '=', 'erm_dokters.id')
            ->join('users', 'erm_dokters.user_id', '=', 'users.id')
            ->where('finance_invoices.amount_paid', '>', 0);

        // Apply date range or year filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        // Apply clinic filter
        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'erm_dokters.id as doctor_id',
            'users.name as doctor_name',
            DB::raw('SUM(finance_invoices.total_amount) as total_revenue')
        )
            ->groupBy('erm_dokters.id', 'users.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('doctor_name')->toArray(),
            'series' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getProfitablePatients($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
            ->where('finance_invoices.amount_paid', '>', 0);

        // Apply date range or year filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        // Apply clinic filter
        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'erm_pasiens.id as patient_id',
            'erm_pasiens.nama as patient_name',
            DB::raw('SUM(finance_invoices.total_amount) as total_spent'),
            DB::raw('COUNT(DISTINCT finance_invoices.id) as visit_count')
        )
            ->groupBy('erm_pasiens.id', 'erm_pasiens.nama')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('patient_name')->toArray(),
            'spending' => $data->pluck('total_spent')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'visits' => $data->pluck('visit_count')->toArray()
        ];
    }

    private function getRevenueByTreatmentCategory($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        try {
            // First try with spesialisasi join
            $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
                ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
                ->join('erm_tindakan', 'finance_invoice_items.billable_id', '=', 'erm_tindakan.id')
                ->leftJoin('erm_spesialisasis', 'erm_tindakan.spesialis_id', '=', 'erm_spesialisasis.id')
                ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Tindakan')
                ->where('finance_invoices.amount_paid', '>', 0);

            // Apply date range or year filter
            if ($startDate && $endDate) {
                $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
            } else {
                $query->whereYear('erm_visitations.tanggal_visitation', $year);
            }

            // Apply clinic filter
            if ($clinicId) {
                $query->where('erm_visitations.klinik_id', $clinicId);
            }

            $data = $query->select(
                DB::raw('COALESCE(erm_spesialisasis.nama, "General Treatment") as category_name'),
                DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue'),
                DB::raw('COUNT(*) as treatment_count')
            )
                ->groupBy('category_name')
                ->orderBy('total_revenue', 'desc')
                ->get();

            return [
                'labels' => $data->pluck('category_name')->toArray(),
                'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                    return floatval($val);
                })->toArray(),
                'count' => $data->pluck('treatment_count')->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('getRevenueByTreatmentCategory error: ' . $e->getMessage());
            
            // Fallback: Group by treatment name instead
            try {
                $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
                    ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
                    ->join('erm_tindakan', 'finance_invoice_items.billable_id', '=', 'erm_tindakan.id')
                    ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Tindakan')
                    ->where('finance_invoices.amount_paid', '>', 0);

                // Apply date range or year filter
                if ($startDate && $endDate) {
                    $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
                } else {
                    $query->whereYear('erm_visitations.tanggal_visitation', $year);
                }

                // Apply clinic filter
                if ($clinicId) {
                    $query->where('erm_visitations.klinik_id', $clinicId);
                }

                $data = $query->select(
                    'erm_tindakan.nama as category_name',
                    DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue'),
                    DB::raw('COUNT(*) as treatment_count')
                )
                    ->groupBy('erm_tindakan.nama')
                    ->orderBy('total_revenue', 'desc')
                    ->limit(10)
                    ->get();

                return [
                    'labels' => $data->pluck('category_name')->toArray(),
                    'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                        return floatval($val);
                    })->toArray(),
                    'count' => $data->pluck('treatment_count')->toArray()
                ];
            } catch (\Exception $e2) {
                Log::error('Fallback getRevenueByTreatmentCategory error: ' . $e2->getMessage());
                return [
                    'labels' => ['No Data'],
                    'revenue' => [0],
                    'count' => [0]
                ];
            }
        }
    }

    private function getPaymentMethodAnalysis($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoices.amount_paid', '>', 0);

        // Apply date range or year filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        // Apply clinic filter
        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'finance_invoices.payment_method',
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(finance_invoices.total_amount) as total_revenue')
        )
            ->groupBy('finance_invoices.payment_method')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'labels' => $data->pluck('payment_method')->toArray(),
            'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'count' => $data->pluck('transaction_count')->toArray()
        ];
    }

    private function getRevenueGrowthComparison($year, $startDate = null, $endDate = null, $clinicId = null)
    {
        $currentYearRevenue = $this->getMonthlyRevenue($year, $startDate, $endDate, $clinicId);
        $previousYearRevenue = $this->getMonthlyRevenue($year - 1, $startDate, $endDate, $clinicId);

        $growth = [];
        for ($i = 0; $i < 12; $i++) {
            $current = $currentYearRevenue['series'][$i] ?? 0;
            $previous = $previousYearRevenue['series'][$i] ?? 0;
            
            if ($previous > 0) {
                $growthPercent = round((($current - $previous) / $previous) * 100, 1);
            } else {
                $growthPercent = $current > 0 ? 100 : 0;
            }
            
            $growth[] = $growthPercent;
        }

        return [
            'labels' => $currentYearRevenue['labels'],
            'current_year' => $currentYearRevenue['series'],
            'previous_year' => $previousYearRevenue['series'],
            'growth_percentage' => $growth
        ];
    }

    private function getDailyRevenueTrends($year, $month, $startDate = null, $endDate = null, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoices.amount_paid', '>', 0);

        // Apply date range or year/month filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year)
                ->whereMonth('erm_visitations.tanggal_visitation', $month);
        }

        // Apply clinic filter
        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            DB::raw('DAY(erm_visitations.tanggal_visitation) as day'),
            DB::raw('SUM(finance_invoices.total_amount) as revenue')
        )
            ->groupBy(DB::raw('DAY(erm_visitations.tanggal_visitation)'))
            ->orderBy('day')
            ->get();

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $dailyData = array_fill(0, $daysInMonth, 0);

        foreach ($data as $item) {
            $dailyData[$item->day - 1] = floatval($item->revenue);
        }

        $labels = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
        }

        return [
            'labels' => $labels,
            'series' => $dailyData
        ];
    }

    private function getAgeDemographics($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        $ageRanges = [
            '0-17' => [0, 17],
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '56-65' => [56, 65],
            '65+' => [66, 200]
        ];

        $results = [];

        foreach ($ageRanges as $label => $range) {
            $query = Pasien::whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= ?', [$range[0]])
                ->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <= ?', [$range[1]]);

            if ($clinicId || $year || $month || $startDate || $endDate) {
                $query->whereHas('visitations', function ($q) use ($clinicId, $year, $month, $startDate, $endDate) {
                    if ($clinicId) $q->where('klinik_id', $clinicId);
                    
                    // Apply date range or year/month filter
                    if ($startDate && $endDate) {
                        $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                    } else {
                        if ($year) $q->whereYear('tanggal_visitation', $year);
                        if ($month) $q->whereMonth('tanggal_visitation', $month);
                    }
                });
            }

            $results[$label] = $query->count();
        }

        return [
            'labels' => array_keys($results),
            'series' => array_values($results)
        ];
    }

    private function getGenderDemographics($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Pasien::selectRaw('gender, count(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender');

        if ($clinicId || $year || $month || $startDate || $endDate) {
            $query->whereHas('visitations', function ($q) use ($clinicId, $year, $month, $startDate, $endDate) {
                if ($clinicId) $q->where('klinik_id', $clinicId);
                
                // Apply date range or year/month filter
                if ($startDate && $endDate) {
                    $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                } else {
                    if ($year) $q->whereYear('tanggal_visitation', $year);
                    if ($month) $q->whereMonth('tanggal_visitation', $month);
                }
            });
        }

        $data = $query->get();

        $labels = [];
        $series = [];

        foreach ($data as $item) {
            $gender = $item->gender == 'Laki-laki' ? 'Male' : ($item->gender == 'Perempuan' ? 'Female' : $item->gender);
            $labels[] = $gender;
            $series[] = $item->count;
        }

        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    private function getPatientLoyalty($year, $clinicId = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Visitation::join('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id');
        
        // Apply date range or year/month filter
        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            if ($year) $query->whereYear('erm_visitations.tanggal_visitation', $year);
            if ($month) $query->whereMonth('erm_visitations.tanggal_visitation', $month);
        }
        
        if ($clinicId) $query->where('erm_visitations.klinik_id', $clinicId);

        $data = $query->select(
            'erm_pasiens.id as patient_id',
            'erm_pasiens.nama as patient_name',
            DB::raw('COUNT(erm_visitations.id) as visit_count')
        )
            ->groupBy('erm_pasiens.id', 'erm_pasiens.nama')
            ->orderBy('visit_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('patient_name')->toArray(),
            'series' => $data->pluck('visit_count')->toArray()
        ];
    }

    private function getGeographicDistribution($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Pasien::join('area_villages', 'erm_pasiens.village_id', '=', 'area_villages.id')
            ->join('area_districts', 'area_villages.district_id', '=', 'area_districts.id')
            ->join('area_regencies', 'area_districts.regency_id', '=', 'area_regencies.id');

        if ($clinicId || $year || $month) {
            $query->whereHas('visitations', function ($q) use ($clinicId, $year, $month) {
                if ($clinicId) $q->where('klinik_id', $clinicId);
                if ($year) $q->whereYear('tanggal_visitation', $year);
                if ($month) $q->whereMonth('tanggal_visitation', $month);
            });
        }

        $data = $query->select(
            'area_regencies.name as regency_name',
            DB::raw('COUNT(erm_pasiens.id) as patient_count')
        )
            ->groupBy('area_regencies.name')
            ->orderBy('patient_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('regency_name')->toArray(),
            'series' => $data->pluck('patient_count')->toArray()
        ];
    }

    private function getPatientGrowthTrends($year, $clinicId = null)
    {
        $query = Pasien::whereYear('created_at', $year);
        
        if ($clinicId) {
            $query->whereHas('visitations', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }

        $data = $query->select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $seriesData = array_fill(0, 12, 0);

        foreach ($data as $item) {
            $seriesData[$item->month - 1] = $item->count;
        }

        return [
            'labels' => $months,
            'series' => $seriesData
        ];
    }

    private function getPatientRetentionAnalysis($year = null, $clinicId = null, $startDate = null, $endDate = null)
    {
        // Build visitation query with provided filters (date range preferred)
        $visitQuery = Visitation::query();

        if ($startDate && $endDate) {
            $visitQuery->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        } else {
            // fallback to year filter if provided
            if ($year) {
                $visitQuery->whereYear('tanggal_visitation', $year);
            }
        }

        if ($clinicId) {
            $visitQuery->where('klinik_id', $clinicId);
        }

        // Group by patient and count visits per patient within the filtered range
        $groups = (clone $visitQuery)
            ->select('pasien_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('pasien_id')
            ->get();

        $totalPatients = $groups->count();
        $returningPatients = $groups->where('cnt', '>=', 2)->count();

        // Average visits per patient (use 1 decimal place)
        $avgVisits = 0;
        if ($totalPatients > 0) {
            $avgVisits = round($groups->avg('cnt'), 1);
        }

        $retentionRate = $totalPatients > 0 ? round(($returningPatients / $totalPatients) * 100, 1) : 0;

        return [
            'total_patients' => $totalPatients,
            'returning_patients' => $returningPatients,
            'retention_rate' => $retentionRate,
            'one_time_patients' => max(0, $totalPatients - $returningPatients),
            'avg_visits_per_patient' => $avgVisits
        ];
    }

    private function getPopularTreatments($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        // Determine date range
        if ($startDate && $endDate) {
            $start = $startDate;
            $end = $endDate;
        } else {
            $end = now();
            $start = $this->getStartDate('year'); // Default to year if no range specified
        }

        $query = InvoiceItem::whereHas('invoice', function ($q) use ($start, $end) {
            $q->whereHas('visitation', function ($v) use ($start, $end) {
                $v->whereBetween('tanggal_visitation', [$start, $end]);
            })
                ->where('amount_paid', '>', 0);
        })
            ->where('billable_type', 'App\\Models\\ERM\\Tindakan');
        
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        
        if (!$startDate && !$endDate) {
            if ($year) {
                $query->whereHas('invoice.visitation', function ($q) use ($year) {
                    $q->whereYear('tanggal_visitation', $year);
                });
            }
            if ($month) {
                $query->whereHas('invoice.visitation', function ($q) use ($month) {
                    $q->whereMonth('tanggal_visitation', $month);
                });
            }
        }
        
        $data = $query->join('erm_tindakan', 'finance_invoice_items.billable_id', '=', 'erm_tindakan.id')
            ->select(
                'finance_invoice_items.billable_id',
                'erm_tindakan.nama as treatment_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(finance_invoice_items.final_amount) as revenue')
            )
            ->groupBy('finance_invoice_items.billable_id', 'erm_tindakan.nama')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'labels' => $data->pluck('treatment_name')->toArray(),
            'values' => $data->pluck('count')->toArray(),
            'revenue' => $data->pluck('revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getPackagePerformance($clinicId = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        // Determine date range
        if ($startDate && $endDate) {
            $start = $startDate;
            $end = $endDate;
        } else {
            $end = now();
            $start = $this->getStartDate('year'); // Default to year if no range specified
        }

        $query = InvoiceItem::whereHas('invoice', function ($q) use ($start, $end) {
            $q->whereHas('visitation', function ($v) use ($start, $end) {
                $v->whereBetween('tanggal_visitation', [$start, $end]);
            })
                ->where('amount_paid', '>', 0);
        })
            ->where('billable_type', 'App\\Models\\ERM\\PaketTindakan');
        
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        
        if (!$startDate && !$endDate) {
            if ($year) {
                $query->whereHas('invoice.visitation', function ($q) use ($year) {
                    $q->whereYear('tanggal_visitation', $year);
                });
            }
            if ($month) {
                $query->whereHas('invoice.visitation', function ($q) use ($month) {
                    $q->whereMonth('tanggal_visitation', $month);
                });
            }
        }
        
        $data = $query->join('erm_paket_tindakan', 'finance_invoice_items.billable_id', '=', 'erm_paket_tindakan.id')
            ->select(
                'finance_invoice_items.billable_id',
                'erm_paket_tindakan.nama as package_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(finance_invoice_items.final_amount) as revenue')
            )
            ->groupBy('finance_invoice_items.billable_id', 'erm_paket_tindakan.nama')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'labels' => $data->pluck('package_name')->toArray(),
            'values' => $data->pluck('revenue')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'count' => $data->pluck('count')->toArray()
        ];
    }

    private function getVisitationTrends($year, $clinicId = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Visitation::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        } else {
            if ($year) $query->whereYear('tanggal_visitation', $year);
            if ($month) $query->whereMonth('tanggal_visitation', $month);
        }
        
        if ($clinicId) $query->where('klinik_id', $clinicId);
        
        $data = $query->select(
            DB::raw('MONTH(tanggal_visitation) as month'),
            DB::raw('COUNT(*) as visit_count')
        )
            ->groupBy(DB::raw('MONTH(tanggal_visitation)'))
            ->orderBy('month')
            ->get();
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $seriesData = array_fill(0, 12, 0);
        foreach ($data as $item) {
            $seriesData[$item->month - 1] = $item->visit_count;
        }
        return [
            'labels' => $months,
            'values' => $seriesData
        ];
    }

    private function getBestSellingProducts($startDate, $endDate, $clinicId = null, $kategori = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
            ->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\ResepFarmasi')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        if ($kategori) {
            $query->where('erm_obat.kategori', $kategori);
        }

        $data = $query->select(
            'erm_obat.nama as product_name',
            'erm_obat.kategori as category',
            DB::raw('SUM(finance_invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue')
        )
            ->groupBy('erm_obat.id', 'erm_obat.nama', 'erm_obat.kategori')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('product_name')->toArray(),
            'values' => $data->pluck('total_quantity')->toArray(),
            'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'categories' => $data->pluck('category')->toArray()
        ];
    }

    private function getMedicationTrends($startDate, $endDate, $clinicId = null, $kategori = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\ResepFarmasi')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        if ($kategori) {
            $query->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
                  ->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
                  ->where('erm_obat.kategori', $kategori);
        }

        // Get daily trends over the date range
        $data = $query->select(
            DB::raw('DATE(erm_visitations.tanggal_visitation) as date'),
            DB::raw('SUM(finance_invoice_items.quantity) as total_quantity')
        )
            ->groupBy(DB::raw('DATE(erm_visitations.tanggal_visitation)'))
            ->orderBy('date')
            ->get();

        $dates = [];
        $quantities = [];

        foreach ($data as $item) {
            $dates[] = date('M d', strtotime($item->date));
            $quantities[] = $item->total_quantity;
        }

        return [
            'labels' => $dates,
            'values' => $quantities
        ];
    }

    private function getClinicRevenueComparison($year)
    {
        $clinics = Klinik::all();
        $clinicNames = [];
        $clinicRevenue = [];

        foreach ($clinics as $clinic) {
            $clinicNames[] = $clinic->nama;

            $revenue = Invoice::whereHas('visitation', function ($q) use ($clinic, $year) {
                $q->where('klinik_id', $clinic->id)
                    ->whereYear('tanggal_visitation', $year);
            })
                ->where('amount_paid', '>', 0)
                ->sum('total_amount');

            $clinicRevenue[] = floatval($revenue);
        }

        return [
            'labels' => $clinicNames,
            'series' => $clinicRevenue
        ];
    }

    private function getClinicPatientComparison($year)
    {
        $clinics = Klinik::all();
        $clinicNames = [];
        $patientCounts = [];

        foreach ($clinics as $clinic) {
            $clinicNames[] = $clinic->nama;

            $count = Visitation::where('klinik_id', $clinic->id)
                ->whereYear('tanggal_visitation', $year)
                ->distinct('pasien_id')
                ->count('pasien_id');

            $patientCounts[] = $count;
        }

        return [
            'labels' => $clinicNames,
            'series' => $patientCounts
        ];
    }

    private function getClinicTreatmentComparison($year)
    {
        $clinics = Klinik::all();
        $clinicNames = [];
        $treatmentCounts = [];

        foreach ($clinics as $clinic) {
            $clinicNames[] = $clinic->nama;

            $count = InvoiceItem::whereHas('invoice.visitation', function ($q) use ($clinic, $year) {
                $q->where('klinik_id', $clinic->id)
                    ->whereYear('tanggal_visitation', $year);
            })
                ->where('billable_type', 'App\Models\ERM\Tindakan')
                ->count();

            $treatmentCounts[] = $count;
        }

        return [
            'labels' => $clinicNames,
            'series' => $treatmentCounts
        ];
    }

    private function getAvgRevenuePerPatient($year)
    {
        $clinics = Klinik::all();
        $clinicNames = [];
        $avgRevenue = [];

        foreach ($clinics as $clinic) {
            $clinicNames[] = $clinic->nama;

            $totalRevenue = Invoice::whereHas('visitation', function ($q) use ($clinic, $year) {
                $q->where('klinik_id', $clinic->id)
                    ->whereYear('tanggal_visitation', $year);
            })
                ->where('amount_paid', '>', 0)
                ->sum('total_amount');

            $patientCount = Visitation::where('klinik_id', $clinic->id)
                ->whereYear('tanggal_visitation', $year)
                ->distinct('pasien_id')
                ->count('pasien_id');

            $avg = $patientCount > 0 ? $totalRevenue / $patientCount : 0;
            $avgRevenue[] = round(floatval($avg), 2);
        }

        return [
            'labels' => $clinicNames,
            'series' => $avgRevenue
        ];
    }

    private function getStartDate($period)
    {
        switch ($period) {
            case 'month':
                return now()->subMonth();
            case 'quarter':
                return now()->subMonths(3);
            case 'year':
                return now()->subYear();
            default:
                return now()->subMonth();
        }
    }

    // AJAX endpoint for patient analytics charts
    public function patientsAnalyticsData(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $clinicId = $request->input('clinic_id');

            // If no date range provided, default to current year
            if (!$startDate || !$endDate) {
                $year = $year ?: date('Y');
            }

            $data = [
                'ageDemographics' => $this->getAgeDemographics($clinicId, $year, $month, $startDate, $endDate),
                'genderDemographics' => $this->getGenderDemographics($clinicId, $year, $month, $startDate, $endDate),
                'patientLoyalty' => $this->getPatientLoyalty($year, $clinicId, $month, $startDate, $endDate),
                'geographicDistribution' => $this->getGeographicDistribution($clinicId, $year, $month, $startDate, $endDate),
                'addressStats' => $this->getAddressStatistics($clinicId, $year, $month, $startDate, $endDate),
                'growthTrends' => $this->getPatientGrowthTrends($year, $clinicId, $startDate, $endDate),
                'retentionAnalysis' => $this->getPatientRetentionAnalysis($year, $clinicId, $startDate, $endDate)
            ];

            // Prepare addressStats for table rendering
            $addressTable = [];
            foreach ($data['addressStats'] as $area => $stats) {
                $addressTable[] = [
                    'area' => $area,
                    'count' => $stats['count'],
                    'percentage' => $stats['percentage'],
                ];
            }

            $data['addressTable'] = $addressTable;

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('AJAX Patient analytics error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: return list of new patients (JSON) for modal/datatable
     */
    public function newPatientsList(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $clinicId = $request->input('clinic_id');

            $query = Pasien::select('erm_pasiens.id', 'nama', 'no_hp', 'tanggal_lahir', 'gender', 'alamat', 'created_at');

            // If clinic filter provided, restrict to patients who had visitations in that clinic
            if ($clinicId) {
                $query = $query->whereHas('visitations', function ($q) use ($clinicId, $startDate, $endDate) {
                    $q->where('klinik_id', $clinicId);
                    if ($startDate && $endDate) {
                        $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                    }
                });
            }

            // If date range provided, prefer created_at filter for new patients
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            $patients = $query->orderByDesc('created_at')->limit(1000)->get();

            return response()->json([
                'success' => true,
                'data' => $patients
            ]);
        } catch (\Exception $e) {
            Log::error('newPatientsList error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load new patients: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX endpoint for services analytics charts
    public function servicesAnalyticsData(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $clinicId = $request->input('clinic_id');

            // If no date range provided, default to current year
            if (!$startDate || !$endDate) {
                $year = $year ?: date('Y');
            }

            $data = [
                'summary' => [
                    'total_treatments' => $this->getTotalTreatments($year, $clinicId, $startDate, $endDate),
                    'total_packages' => $this->getTotalPackages($year, $clinicId, $startDate, $endDate)
                ],
                'popularTreatments' => $this->getPopularTreatments($clinicId, $year, $month, $startDate, $endDate),
                'packagePerformance' => $this->getPackagePerformance($clinicId, $year, $month, $startDate, $endDate),
                'visitationTrends' => $this->getVisitationTrends($year, $clinicId, $month, $startDate, $endDate),
                'doctorPerformance' => $this->getDoctorPerformanceAnalysis($year, $clinicId, $startDate, $endDate),
                'treatmentEfficiency' => $this->getTreatmentEfficiencyAnalysis($year, $clinicId, $startDate, $endDate),
                'satisfactionTrends' => $this->getServiceSatisfactionTrends($year, $clinicId, $startDate, $endDate)
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('AJAX Services analytics error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX endpoint for products analytics charts
    public function productsAnalyticsData(Request $request)
    {
        try {
            $clinicId = $request->get('clinic_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $kategori = $request->get('kategori');

            // Summary metrics
            $totalProductsSold = $this->getTotalProductsSold($startDate, $endDate, $clinicId, $kategori);
            $totalMedications = $this->getTotalMedicationCount($startDate, $endDate, $clinicId, $kategori);
            $avgInventoryTurnover = $this->getAvgInventoryTurnover($startDate, $endDate, $clinicId, $kategori);

            // Chart data
            $bestSellingProducts = $this->getBestSellingProducts($startDate, $endDate, $clinicId, $kategori);
            $medicationTrends = $this->getMedicationTrends($startDate, $endDate, $clinicId, $kategori);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_products_sold' => $totalProductsSold,
                        'total_medications' => $totalMedications,
                        'avg_inventory_turnover' => $avgInventoryTurnover
                    ],
                    'charts' => [
                        'best_selling_products' => $bestSellingProducts,
                        'medication_trends' => $medicationTrends
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: return full list of sold products for given filters (no limit)
     */
    public function productsAnalyticsAllData(Request $request)
    {
        try {
            $clinicId = $request->get('clinic_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $kategori = $request->get('kategori');

            $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
                ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
                ->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
                ->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
                ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
                ->where('finance_invoices.amount_paid', '>', 0);

            if ($clinicId) {
                $query->where('erm_visitations.klinik_id', $clinicId);
            }

            if ($kategori) {
                $query->where('erm_obat.kategori', $kategori);
            }

            $data = $query->select(
                'erm_obat.id as product_id',
                'erm_obat.nama as product_name',
                'erm_obat.kategori as category',
                DB::raw('SUM(finance_invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue')
            )
                ->groupBy('erm_obat.id', 'erm_obat.nama', 'erm_obat.kategori')
                ->orderBy('total_quantity', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching full products analytics list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products list: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper methods for products analytics
    private function getTotalProductsSold($startDate, $endDate, $clinicId = null, $kategori = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\ResepFarmasi')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        if ($kategori) {
            $query->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
                  ->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
                  ->where('erm_obat.kategori', $kategori);
        }

        return $query->sum('finance_invoice_items.quantity');
    }

    private function getTotalMedicationCount($startDate, $endDate, $clinicId = null, $kategori = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\ResepFarmasi')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        if ($kategori) {
            $query->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
                  ->where('erm_obat.kategori', $kategori);
        }

        return $query->distinct('erm_resepfarmasi.obat_id')->count();
    }

    private function getAvgInventoryTurnover($startDate, $endDate, $clinicId = null, $kategori = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepfarmasi', 'finance_invoice_items.billable_id', '=', 'erm_resepfarmasi.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\ResepFarmasi')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        if ($kategori) {
            $query->join('erm_obat', 'erm_resepfarmasi.obat_id', '=', 'erm_obat.id')
                  ->where('erm_obat.kategori', $kategori);
        }

        $totalQuantity = $query->sum('finance_invoice_items.quantity');
        $uniqueProducts = $query->distinct('erm_resepfarmasi.obat_id')->count();
        
        return $uniqueProducts > 0 ? round($totalQuantity / $uniqueProducts, 2) : 0;
    }

    // NEW ENHANCED ANALYTICS METHODS

    private function getDoctorPerformanceAnalysis($year, $clinicId = null, $startDate = null, $endDate = null)
    {
        $query = Visitation::join('erm_dokters', 'erm_visitations.dokter_id', '=', 'erm_dokters.id')
            ->join('users', 'erm_dokters.user_id', '=', 'users.id');

        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'users.name as doctor_name',
            DB::raw('COUNT(DISTINCT erm_visitations.pasien_id) as unique_patients'),
            DB::raw('COUNT(erm_visitations.id) as total_visits'),
            DB::raw('COALESCE(SUM(finance_invoices.total_amount), 0) as total_revenue')
        )
            ->leftJoin('finance_invoices', 'erm_visitations.id', '=', 'finance_invoices.visitation_id')
            ->where('finance_invoices.amount_paid', '>', 0)
            ->groupBy('erm_dokters.id', 'users.name')
            ->orderBy('total_visits', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('doctor_name')->toArray(),
            'patients' => $data->pluck('unique_patients')->toArray(),
            'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'visits' => $data->pluck('total_visits')->toArray()
        ];
    }

    private function getTreatmentEfficiencyAnalysis($year, $clinicId = null, $startDate = null, $endDate = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Tindakan')
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($startDate && $endDate) {
            $query->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        } else {
            $query->whereYear('erm_visitations.tanggal_visitation', $year);
        }

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->join('erm_tindakan', 'finance_invoice_items.billable_id', '=', 'erm_tindakan.id')
            ->select(
                'finance_invoice_items.billable_id',
                'erm_tindakan.nama as treatment_name',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('AVG(finance_invoice_items.final_amount) as avg_price'),
                DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue')
            )
            ->groupBy('finance_invoice_items.billable_id', 'erm_tindakan.nama')
            ->orderBy('frequency', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('treatment_name')->toArray(),
            'frequency' => $data->pluck('frequency')->toArray(),
            'avg_price' => $data->pluck('avg_price')->map(function ($val) {
                return round(floatval($val), 0);
            })->toArray(),
            'total_revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray(),
            'efficiency_rate' => $data->count() > 0 ? round($data->avg('frequency'), 1) : 0
        ];
    }

    private function getServiceSatisfactionTrends($year, $clinicId = null, $startDate = null, $endDate = null)
    {
        // This would need a customer satisfaction table/survey system
        // For now, return dummy data structure that can be implemented later
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'satisfaction_score' => [4.2, 4.3, 4.1, 4.4, 4.5, 4.3, 4.6, 4.4, 4.5, 4.7, 4.6, 4.8],
            'response_rate' => [85, 87, 82, 88, 90, 85, 92, 89, 91, 94, 92, 95]
        ];
    }

    private function getProductCategoryPerformance($year, $clinicId = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_obat', 'finance_invoice_items.billable_id', '=', 'erm_obat.id')
            ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Obat')
            ->whereYear('erm_visitations.tanggal_visitation', $year)
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'erm_obat.kategori',
            DB::raw('COUNT(*) as total_sales'),
            DB::raw('SUM(finance_invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue')
        )
            ->whereNotNull('erm_obat.kategori')
            ->groupBy('erm_obat.kategori')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'labels' => $data->pluck('kategori')->toArray(),
            'sales_count' => $data->pluck('total_sales')->toArray(),
            'quantity' => $data->pluck('total_quantity')->toArray(),
            'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getInventoryTurnoverAnalysis($year, $clinicId = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_obat', 'finance_invoice_items.billable_id', '=', 'erm_obat.id')
            ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Obat')
            ->whereYear('erm_visitations.tanggal_visitation', $year)
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'finance_invoice_items.billable_id',
            'erm_obat.nama',
            'erm_obat.stok',
            DB::raw('SUM(finance_invoice_items.quantity) as total_sold'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->groupBy('finance_invoice_items.billable_id', 'erm_obat.nama', 'erm_obat.stok')
            ->orderBy('total_sold', 'desc')
            ->limit(15)
            ->get();

        $turnoverRates = $data->map(function ($item) {
            $turnoverRate = $item->stok > 0 ? round($item->total_sold / $item->stok, 2) : 0;
            return [
                'name' => $item->nama,
                'turnover_rate' => $turnoverRate,
                'total_sold' => $item->total_sold,
                'current_stock' => $item->stok,
                'transactions' => $item->transaction_count
            ];
        })->sortByDesc('turnover_rate');

        return [
            'labels' => $turnoverRates->pluck('name')->toArray(),
            'turnover_rates' => $turnoverRates->pluck('turnover_rate')->toArray(),
            'total_sold' => $turnoverRates->pluck('total_sold')->toArray(),
            'current_stock' => $turnoverRates->pluck('current_stock')->toArray()
        ];
    }

    private function getProductProfitabilityAnalysis($year, $clinicId = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_obat', 'finance_invoice_items.billable_id', '=', 'erm_obat.id')
            ->where('finance_invoice_items.billable_type', 'App\\Models\\ERM\\Obat')
            ->whereYear('erm_visitations.tanggal_visitation', $year)
            ->where('finance_invoices.amount_paid', '>', 0);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

        $data = $query->select(
            'finance_invoice_items.billable_id',
            'erm_obat.nama',
            'erm_obat.hpp',
            DB::raw('SUM(finance_invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(finance_invoice_items.final_amount) as total_revenue'),
            DB::raw('AVG(finance_invoice_items.unit_price) as avg_selling_price')
        )
            ->whereNotNull('erm_obat.hpp')
            ->groupBy('finance_invoice_items.billable_id', 'erm_obat.nama', 'erm_obat.hpp')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        $profitabilityData = $data->map(function ($item) {
            $costOfGoodsSold = $item->hpp * $item->total_quantity;
            $profit = $item->total_revenue - $costOfGoodsSold;
            $profitMargin = $item->total_revenue > 0 ? round(($profit / $item->total_revenue) * 100, 1) : 0;
            
            return [
                'name' => $item->nama,
                'revenue' => floatval($item->total_revenue),
                'cost' => floatval($costOfGoodsSold),
                'profit' => floatval($profit),
                'profit_margin' => $profitMargin,
                'quantity' => $item->total_quantity
            ];
        });

        return [
            'labels' => $profitabilityData->pluck('name')->toArray(),
            'revenue' => $profitabilityData->pluck('revenue')->toArray(),
            'cost' => $profitabilityData->pluck('cost')->toArray(),
            'profit' => $profitabilityData->pluck('profit')->toArray(),
            'profit_margin' => $profitabilityData->pluck('profit_margin')->toArray()
        ];
    }

    // Helper methods for summary data
    private function getTotalTreatments($year, $clinicId = null, $startDate = null, $endDate = null)
    {
        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate, $year) {
            if ($startDate && $endDate) {
                $q->whereHas('visitation', function ($v) use ($startDate, $endDate) {
                    $v->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                });
            } else {
                $q->whereHas('visitation', function ($v) use ($year) {
                    $v->whereYear('tanggal_visitation', $year);
                });
            }
            $q->where('amount_paid', '>', 0);
        })->where('billable_type', 'App\\Models\\ERM\\Tindakan');
        
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        
        return $query->count();
    }

    private function getTotalPackages($year, $clinicId = null, $startDate = null, $endDate = null)
    {
        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate, $year) {
            if ($startDate && $endDate) {
                $q->whereHas('visitation', function ($v) use ($startDate, $endDate) {
                    $v->whereBetween('tanggal_visitation', [$startDate, $endDate]);
                });
            } else {
                $q->whereHas('visitation', function ($v) use ($year) {
                    $v->whereYear('tanggal_visitation', $year);
                });
            }
            $q->where('amount_paid', '>', 0);
        })->where('billable_type', 'App\\Models\\ERM\\PaketTindakan');
        
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        
        return $query->count();
    }
}
