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
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarketingController extends Controller
{
    public function dashboard()
    {
        // Main dashboard stats
        // You can add summary metrics here
        return view('marketing.dashboard');
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
     * @return array
     */
    private function getAddressStatistics($clinicId = null)
    {
        $areas = ['Laweyan', 'Banjarsari', 'Serengan', 'Pasar Kliwon', 'Jebres', 'Sukoharjo', 'Wonogiri', 'Karanganyar'];
        $stats = [];
        
        // Base query
        $query = Pasien::query();
        
        // Apply clinic filter if provided
        if ($clinicId) {
            $query = $query->whereHas('visitations', function($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
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
        $clinicId = $request->input('clinic_id');

        // 1. Monthly Revenue Data
        $monthlyRevenue = $this->getMonthlyRevenue($year, $clinicId);

        // 2. Revenue by Doctor
        $doctorRevenue = $this->getDoctorRevenue($year, $clinicId);

        // 3. Most Profitable Patients
        $topPatients = $this->getProfitablePatients($year, $clinicId);

        $clinics = Klinik::all();

        return view('marketing.revenue', compact(
            'monthlyRevenue',
            'doctorRevenue',
            'topPatients',
            'clinics',
            'year',
            'clinicId'
        ));
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
            // Resep dokter for this visitation
            $resep = $visit->resepDokter()->with('obat')->get()->map(function($r) {
                return [
                    'obat_nama' => $r->obat ? $r->obat->nama : '-',
                    'jumlah' => $r->jumlah,
                    'dosis' => $r->dosis,
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
        $clinicId = $request->input('clinic_id');

        // 1. Age Demographics
        $ageDemographics = $this->getAgeDemographics($clinicId);

        // 2. Gender Demographics
            // Filter by last visit's clinic if provided
            if ($request->has('last_visit_klinik') && $request->last_visit_klinik != 'all') {
                $klinikId = $request->last_visit_klinik;
                $allPasien = $data->get();
                $filtered = $allPasien->filter(function($pasien) use ($klinikId) {
                    $lastVisit = $pasien->visitations()->orderByDesc('tanggal_visitation')->first();
                    return $lastVisit && $lastVisit->klinik_id == $klinikId;
                });
                $data = collect($filtered); // Ensure it's a collection for datatables
            }
        
        // 5. Address Distribution
        $addressStats = $this->getAddressStatistics($clinicId);

        $clinics = Klinik::all();

        return view('marketing.patients', compact(
            'ageDemographics',
            'genderDemographics',
            'patientLoyalty',
            'geographicDistribution',
            'addressStats',
            'clinics',
            'year',
            'clinicId'
        ));
    }

    public function services(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $period = $request->input('period', 'year');
        $clinicId = $request->input('clinic_id');

        // 1. Popular Treatments
        $popularTreatments = $this->getPopularTreatments($period, $clinicId);

        // 2. Treatment Package Performance
        $packagePerformance = $this->getPackagePerformance($period, $clinicId);

        // 3. Visitation Trends (monthly)
        $visitationTrends = $this->getVisitationTrends($year, $clinicId);

        $clinics = Klinik::all();

        return view('marketing.services', compact(
            'popularTreatments',
            'packagePerformance',
            'visitationTrends',
            'clinics',
            'year',
            'period',
            'clinicId'
        ));
    }

    public function products(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $period = $request->input('period', 'year');
        $clinicId = $request->input('clinic_id');

        // 1. Best Selling Products
        $bestSellingProducts = $this->getBestSellingProducts($period, $clinicId);

        // 2. Medication Trends
        $medicationTrends = $this->getMedicationTrends($year, $clinicId);

        $clinics = Klinik::all();

        return view('marketing.products', compact(
            'bestSellingProducts',
            'medicationTrends',
            'clinics',
            'year',
            'period',
            'clinicId'
        ));
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

    private function getMonthlyRevenue($year, $clinicId = null)
    {
        $query = Invoice::whereNotNull('payment_date')
            ->whereYear('payment_date', $year)
            ->where('status', 'paid');

        if ($clinicId) {
            $query->whereHas('visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }

        $data = $query->select(
            DB::raw('MONTH(payment_date) as month'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->groupBy(DB::raw('MONTH(payment_date)'))
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

    private function getDoctorRevenue($year, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_dokters', 'erm_visitations.dokter_id', '=', 'erm_dokters.id')
            ->join('users', 'erm_dokters.user_id', '=', 'users.id')
            ->whereNotNull('finance_invoices.payment_date')
            ->whereYear('finance_invoices.payment_date', $year)
            ->where('finance_invoices.status', 'paid');

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

    private function getProfitablePatients($year, $clinicId = null)
    {
        $query = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
            ->whereNotNull('finance_invoices.payment_date')
            ->whereYear('finance_invoices.payment_date', $year)
            ->where('finance_invoices.status', 'paid');

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

    private function getAgeDemographics($clinicId = null, $year = null, $month = null)
    {
        $ageRanges = [
            'Under 18' => [0, 17],
            '18-30' => [18, 30],
            '31-45' => [31, 45],
            '46-60' => [46, 60],
            'Over 60' => [61, 200]
        ];

        $results = [];

        foreach ($ageRanges as $label => $range) {
            $query = Pasien::whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= ?', [$range[0]])
                ->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <= ?', [$range[1]]);

            if ($clinicId) {
                $query->whereHas('visitations', function ($q) use ($clinicId, $year, $month) {
                    $q->where('klinik_id', $clinicId);
                    if ($year) $q->whereYear('tanggal_visitation', $year);
                    if ($month) $q->whereMonth('tanggal_visitation', $month);
                });
            } elseif ($year || $month) {
                $query->whereHas('visitations', function ($q) use ($year, $month) {
                    if ($year) $q->whereYear('tanggal_visitation', $year);
                    if ($month) $q->whereMonth('tanggal_visitation', $month);
                });
            }

            $results[$label] = $query->count();
        }

        return [
            'labels' => array_keys($results),
            'series' => array_values($results)
        ];
    }

    private function getGenderDemographics($clinicId = null, $year = null, $month = null)
    {
        $query = Pasien::selectRaw('gender, count(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender');

        if ($clinicId || $year || $month) {
            $query->whereHas('visitations', function ($q) use ($clinicId, $year, $month) {
                if ($clinicId) $q->where('klinik_id', $clinicId);
                if ($year) $q->whereYear('tanggal_visitation', $year);
                if ($month) $q->whereMonth('tanggal_visitation', $month);
            });
        }

        $data = $query->get();

        $labels = [];
        $series = [];

        foreach ($data as $item) {
            $gender = $item->gender == 'M' ? 'Male' : ($item->gender == 'F' ? 'Female' : $item->gender);
            $labels[] = $gender;
            $series[] = $item->count;
        }

        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    private function getPatientLoyalty($year, $clinicId = null, $month = null)
    {
        $query = Visitation::join('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id');
        if ($year) $query->whereYear('erm_visitations.tanggal_visitation', $year);
        if ($month) $query->whereMonth('erm_visitations.tanggal_visitation', $month);
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

    private function getGeographicDistribution($clinicId = null, $year = null, $month = null)
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

    private function getPopularTreatments($period, $clinicId = null, $year = null, $month = null)
    {
        $endDate = now();
        $startDate = $this->getStartDate($period);
        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid');
        })
            ->where('billable_type', 'App\\Models\\ERM\\Tindakan');
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
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
        $data = $query->select(
            'billable_id',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(final_amount) as revenue')
        )
            ->with('billable:id,nama')
            ->groupBy('billable_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        return [
            'labels' => $data->map(function ($item) {
                return $item->billable->nama ?? 'Unknown';
            })->toArray(),
            'count' => $data->pluck('count')->toArray(),
            'revenue' => $data->pluck('revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getPackagePerformance($period, $clinicId = null, $year = null, $month = null)
    {
        $endDate = now();
        $startDate = $this->getStartDate($period);
        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid');
        })
            ->where('billable_type', 'App\\Models\\ERM\\PaketTindakan');
        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
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
        $data = $query->select(
            'billable_id',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(final_amount) as revenue')
        )
            ->with('billable:id,nama')
            ->groupBy('billable_id')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();
        return [
            'labels' => $data->map(function ($item) {
                return $item->billable->nama ?? 'Unknown';
            })->toArray(),
            'count' => $data->pluck('count')->toArray(),
            'revenue' => $data->pluck('revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getVisitationTrends($year, $clinicId = null, $month = null)
    {
        $query = Visitation::query();
        if ($year) $query->whereYear('tanggal_visitation', $year);
        if ($month) $query->whereMonth('tanggal_visitation', $month);
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
            'series' => $seriesData
        ];
    }

    private function getBestSellingProducts($period, $clinicId = null)
    {
        // Calculate date range based on period
        $endDate = now();
        $startDate = $this->getStartDate($period);

        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid');
        })
            ->where('billable_type', 'App\Models\ERM\Obat');

        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }

        $data = $query->select(
            'billable_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(final_amount) as total_revenue')
        )
            ->with('billable:id,nama')
            ->groupBy('billable_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->map(function ($item) {
                return $item->billable->nama ?? 'Unknown';
            })->toArray(),
            'quantity' => $data->pluck('total_quantity')->toArray(),
            'revenue' => $data->pluck('total_revenue')->map(function ($val) {
                return floatval($val);
            })->toArray()
        ];
    }

    private function getMedicationTrends($year, $clinicId = null)
    {
        $query = InvoiceItem::join('finance_invoices', 'finance_invoice_items.invoice_id', '=', 'finance_invoices.id')
            ->where('finance_invoice_items.billable_type', 'App\Models\ERM\Obat')
            ->whereYear('finance_invoices.payment_date', $year)
            ->where('finance_invoices.status', 'paid');

        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }

        $data = $query->select(
            DB::raw('MONTH(finance_invoices.payment_date) as month'),
            DB::raw('SUM(finance_invoice_items.quantity) as total_quantity')
        )
            ->groupBy(DB::raw('MONTH(finance_invoices.payment_date)'))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $seriesData = array_fill(0, 12, 0); // Initialize with zeros

        foreach ($data as $item) {
            $seriesData[$item->month - 1] = $item->total_quantity;
        }

        return [
            'labels' => $months,
            'series' => $seriesData
        ];
    }

    private function getClinicRevenueComparison($year)
    {
        $clinics = Klinik::all();
        $clinicNames = [];
        $clinicRevenue = [];

        foreach ($clinics as $clinic) {
            $clinicNames[] = $clinic->nama;

            $revenue = Invoice::whereHas('visitation', function ($q) use ($clinic) {
                $q->where('klinik_id', $clinic->id);
            })
                ->whereYear('payment_date', $year)
                ->where('status', 'paid')
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
                ->where('status', 'paid')
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
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $clinicId = $request->input('clinic_id');

        $ageDemographics = $this->getAgeDemographics($clinicId, $year, $month);
        $genderDemographics = $this->getGenderDemographics($clinicId, $year, $month);
        $patientLoyalty = $this->getPatientLoyalty($year, $clinicId, $month);
        $geographicDistribution = $this->getGeographicDistribution($clinicId, $year, $month);
        $addressStats = $this->getAddressStatistics($clinicId, $year, $month);

        // Prepare addressStats for table rendering
        $addressTable = [];
        foreach ($addressStats as $area => $stats) {
            $addressTable[] = [
                'area' => $area,
                'count' => $stats['count'],
                'percentage' => $stats['percentage'],
            ];
        }

        return response()->json([
            'ageDemographics' => $ageDemographics,
            'genderDemographics' => $genderDemographics,
            'patientLoyalty' => $patientLoyalty,
            'geographicDistribution' => $geographicDistribution,
            'addressStats' => $addressStats,
            'addressTable' => $addressTable,
        ]);
    }

    // AJAX endpoint for services analytics charts
    public function servicesAnalyticsData(Request $request)
    {
        $period = $request->input('period', 'year');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $clinicId = $request->input('clinic_id');

        $popularTreatments = $this->getPopularTreatments($period, $clinicId, $year, $month);
        $packagePerformance = $this->getPackagePerformance($period, $clinicId, $year, $month);
        $visitationTrends = $this->getVisitationTrends($year, $clinicId, $month);

        return response()->json([
            'popularTreatments' => $popularTreatments,
            'packagePerformance' => $packagePerformance,
            'visitationTrends' => $visitationTrends,
            'year' => $year,
        ]);
    }
}
