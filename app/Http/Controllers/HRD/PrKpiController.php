<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrKpi;
use App\Models\HRD\Employee;

class PrKpiController extends Controller
{
    public function index()
    {
        return view('hrd.payroll.kpi.index');
    }

    public function data(Request $request)
    {
        $query = PrKpi::query();
        return datatables()->of($query)
            ->addColumn('aksi', function($row) {
                return '<button class="btn btn-sm btn-warning btn-edit">Edit</button> <button class="btn btn-sm btn-danger btn-delete">Delete</button>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_poin' => 'required|string',
            'initial_poin' => 'required|numeric',
        ]);
        $row = PrKpi::create($validated);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_poin' => 'required|string',
            'initial_poin' => 'required|numeric',
        ]);
        $row = PrKpi::findOrFail($id);
        $row->update($validated);

        // If Marketing KPI is updated, update slip gaji poin_marketing and kpi_poin for current month
        if ($row->nama_poin === 'Marketing') {
            $bulan = date('Y-m'); // current month in YYYY-MM format
            $initialPoinMarketing = $row->initial_poin;
            $slips = \App\Models\HRD\PrSlipGaji::where('bulan', $bulan)->get();
            foreach ($slips as $slip) {
                // Recalculate kpi_poin (assuming: poin_marketing + poin_penilaian + poin_kehadiran)
                $kpi_poin = $initialPoinMarketing + ($slip->poin_penilaian ?? 0) + ($slip->poin_kehadiran ?? 0);
                $slip->poin_marketing = $initialPoinMarketing;
                $slip->kpi_poin = $kpi_poin;
                $slip->save();
            }
        }
        // Recalculate only uang_kpi for all employees for the current month
        $bulan = date('Y-m'); // current month in YYYY-MM format
        // Calculate total incentive pool by converting each omset entry into its incentive amount
        $omsetRows = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->get();
        $totalOmset = 0;
        foreach ($omsetRows as $row) {
            $insentif = $row->insentifOmset;
            if ($insentif) {
                $nominal = floatval($row->nominal);
                $insValue = 0;
                if ($insentif->omset_min !== null && $insentif->omset_max !== null) {
                    if ($nominal >= $insentif->omset_min && $nominal <= $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_normal);
                    } elseif ($nominal > $insentif->omset_max) {
                        $insValue = floatval($insentif->insentif_up);
                    }
                } else {
                    $insValue = floatval($insentif->insentif_normal ?? 0);
                }
                $totalOmset += ($insValue / 100) * $nominal;
            } else {
                $totalOmset += floatval($row->nominal);
            }
        }
        $employees = \App\Models\HRD\Employee::all();
        // Collect kpi_poin for all employees
        $employeeKpiPoin = [];
        foreach ($employees as $employee) {
            $employeeKpiPoin[$employee->id] = \App\Models\HRD\PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->value('kpi_poin') ?? 0;
        }
        $totalKpiPoin = array_sum($employeeKpiPoin);
        foreach ($employees as $employee) {
            $kpiPoin = $employeeKpiPoin[$employee->id];
            $uangKpi = ($totalKpiPoin > 0) ? ($kpiPoin / $totalKpiPoin * $totalOmset) : 0;
            \App\Models\HRD\PrSlipGaji::where('employee_id', $employee->id)
                ->where('bulan', $bulan)
                ->update(['uang_kpi' => $uangKpi]);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function destroy($id)
    {
        $row = PrKpi::findOrFail($id);
        $row->delete();
        return response()->json(['success' => true]);
    }
}
