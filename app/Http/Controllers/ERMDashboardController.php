<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;
use Illuminate\Support\Carbon;

class ERMDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Dokter', 'Perawat', 'Pendaftaran', 'Admin', 'Farmasi','Beautician'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }
        $dokterId = auth()->user()->dokter->id ?? null;
        $totalVisit = 0;
        $monthlyVisits = array_fill(1, 12, 0);
        $mostFrequentPatient = null;
        $mostFrequentPatientCount = null;
        $busiestMonth = null;
        $visitsByPayment = [];
        $visitsByDay = [];
        $selectedYear = (int) $request->input('year', now()->year);
        $selectedMonth = (int) $request->input('month', now()->month);
        $year = now()->year;
        if ($dokterId) {
            $totalVisit = Visitation::where('dokter_id', $dokterId)->count();
            $monthlyVisitsRaw = Visitation::selectRaw('MONTH(tanggal_visitation) as month, COUNT(*) as count')
                ->where('dokter_id', $dokterId)
                ->whereYear('tanggal_visitation', $selectedYear)
                ->groupBy('month')
                ->pluck('count', 'month');
            foreach ($monthlyVisitsRaw as $m => $count) {
                $monthlyVisits[$m] = $count;
            }
            // Most frequent patient for selected month and year
            $mostFrequent = Visitation::selectRaw('pasien_id, COUNT(*) as count')
                ->where('dokter_id', $dokterId)
                ->whereYear('tanggal_visitation', $selectedYear)
                ->whereMonth('tanggal_visitation', $selectedMonth)
                ->groupBy('pasien_id')
                ->orderByDesc('count')
                ->first();
            if ($mostFrequent) {
                $pasien = Pasien::find($mostFrequent->pasien_id);
                $mostFrequentPatient = $pasien ? $pasien->nama : '-';
                $mostFrequentPatientCount = $mostFrequent->count;
            }
            // Busiest month (in selected year)
            if (!empty($monthlyVisitsRaw)) {
                $maxMonth = array_keys($monthlyVisitsRaw->toArray(), max($monthlyVisitsRaw->toArray()));
                $busiestMonth = $maxMonth[0] ?? null;
            }
            // Visits by payment method (in selected year)
            $visitsByPayment = Visitation::selectRaw('metode_bayar_id, COUNT(*) as count')
                ->where('dokter_id', $dokterId)
                ->whereYear('tanggal_visitation', $selectedYear)
                ->groupBy('metode_bayar_id')
                ->pluck('count', 'metode_bayar_id')->toArray();
            // Visits by day for selected month and year
            $daysInMonth = now()->setYear($selectedYear)->setMonth($selectedMonth)->daysInMonth;
            $visitsByDayRaw = Visitation::selectRaw('DAY(tanggal_visitation) as day, COUNT(*) as count')
                ->where('dokter_id', $dokterId)
                ->whereYear('tanggal_visitation', $selectedYear)
                ->whereMonth('tanggal_visitation', $selectedMonth)
                ->groupBy('day')
                ->pluck('count', 'day');
            $visitsByDay = array_fill(1, $daysInMonth, 0);
            foreach ($visitsByDayRaw as $d => $count) {
                $visitsByDay[$d] = $count;
            }
        }
        $monthlyVisits = array_values($monthlyVisits);
        $visitsByDay = array_values($visitsByDay);
        // Get year range for dropdown (last 5 years)
        $yearRange = range(now()->year, now()->year - 4);
        return view('erm.dashboard', compact('totalVisit', 'monthlyVisits', 'mostFrequentPatient', 'mostFrequentPatientCount', 'busiestMonth', 'visitsByPayment', 'visitsByDay', 'selectedMonth', 'selectedYear', 'yearRange'));
    }

    public function daftarpasien()
    {
        return view('erm.daftarpasien');
    }
}
