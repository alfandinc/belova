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
            $data = Pasien::select('id', 'nama', 'tanggal_lahir', 'gender', 'alamat');
            
            // Apply area filter if provided
            if ($request->has('area') && $request->area != 'all') {
                $area = $request->area;
                $data = $data->where('alamat', 'like', "%$area%");
            }
            
            return datatables()
                ->of($data)
                ->addColumn('umur', function($row) {
                    return Carbon::parse($row->tanggal_lahir)->age . ' tahun';
                })
                ->addColumn('gender_text', function($row) {
                    return $row->gender == 'L' ? 'Laki-laki' : 'Perempuan';
                })
                ->addColumn('area', function($row) {
                    $areas = ['Laweyan', 'Banjarsari', 'Serengan', 'Pasar Kliwon', 'Jebres', 'Sukoharjo', 'Wonogiri', 'Karanganyar'];
                    foreach($areas as $area) {
                        if (stripos($row->alamat, $area) !== false) {
                            return $area;
                        }
                    }
                    return 'Lainnya';
                })
                ->rawColumns(['umur', 'gender_text', 'area'])
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

    public function patients(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $clinicId = $request->input('clinic_id');

        // 1. Age Demographics
        $ageDemographics = $this->getAgeDemographics($clinicId);

        // 2. Gender Demographics
        $genderDemographics = $this->getGenderDemographics($clinicId);

        // 3. Patient Loyalty (visit frequency)
        $patientLoyalty = $this->getPatientLoyalty($year, $clinicId);

        // 4. Geographic Distribution
        $geographicDistribution = $this->getGeographicDistribution($clinicId);
        
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

    private function getAgeDemographics($clinicId = null)
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
                $query->whereHas('visitation', function ($q) use ($clinicId) {
                    $q->where('klinik_id', $clinicId);
                });
            }

            $results[$label] = $query->count();
        }

        return [
            'labels' => array_keys($results),
            'series' => array_values($results)
        ];
    }

    private function getGenderDemographics($clinicId = null)
    {
        $query = Pasien::selectRaw('gender, count(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender');

        if ($clinicId) {
            $query->whereHas('visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
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

    private function getPatientLoyalty($year, $clinicId = null)
    {
        $query = Visitation::join('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
            ->whereYear('erm_visitations.tanggal_visitation', $year);

        if ($clinicId) {
            $query->where('erm_visitations.klinik_id', $clinicId);
        }

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

    private function getGeographicDistribution($clinicId = null)
    {
        $query = Pasien::join('area_villages', 'erm_pasiens.village_id', '=', 'area_villages.id')
            ->join('area_districts', 'area_villages.district_id', '=', 'area_districts.id')
            ->join('area_regencies', 'area_districts.regency_id', '=', 'area_regencies.id');

        if ($clinicId) {
            $query->whereHas('visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
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

    private function getPopularTreatments($period, $clinicId = null)
    {
        // Calculate date range based on period
        $endDate = now();
        $startDate = $this->getStartDate($period);

        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid');
        })
            ->where('billable_type', 'App\Models\ERM\Tindakan');

        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
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

    private function getPackagePerformance($period, $clinicId = null)
    {
        // Calculate date range based on period
        $endDate = now();
        $startDate = $this->getStartDate($period);

        $query = InvoiceItem::whereHas('invoice', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid');
        })
            ->where('billable_type', 'App\Models\ERM\PaketTindakan');

        if ($clinicId) {
            $query->whereHas('invoice.visitation', function ($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
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

    private function getVisitationTrends($year, $clinicId = null)
    {
        $query = Visitation::whereYear('tanggal_visitation', $year);

        if ($clinicId) {
            $query->where('klinik_id', $clinicId);
        }

        $data = $query->select(
            DB::raw('MONTH(tanggal_visitation) as month'),
            DB::raw('COUNT(*) as visit_count')
        )
            ->groupBy(DB::raw('MONTH(tanggal_visitation)'))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $seriesData = array_fill(0, 12, 0); // Initialize with zeros

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
}
