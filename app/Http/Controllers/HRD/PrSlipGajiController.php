<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrSlipGaji;
use App\Models\HRD\Employee;

class PrSlipGajiController extends Controller

{
    // ...existing code...
        // Return omset input fields for all available penghasil omset
    public function getOmsetInputs(Request $request)
    {
        $bulan = $request->get('bulan');
        $insentifOmset = \App\Models\HRD\PrInsentifOmset::all();
        return view('hrd.payroll.slip_gaji.buat._omset_inputs', compact('insentifOmset'))->render();
    }

    // Store omset bulanan (moved from OmsetBulananController)
    public function storeOmsetBulanan(Request $request)
    {
        $bulan = $request->input('bulan');
        $omsetBulanan = $request->input('omset_bulanan', []);
        $totalOmset = 0;
        foreach ($omsetBulanan as $insentifOmsetId => $nominal) {
            \App\Models\HRD\PrOmsetBulanan::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'insentif_omset_id' => $insentifOmsetId
                ],
                [
                    'nominal' => $nominal
                ]
            );
            $totalOmset += floatval($nominal);
        }
        return response()->json(['success' => true, 'total_omset' => number_format($totalOmset, 2)]);
    }

    // Get total omset for a month (route expects getTotal)
    public function getTotal(Request $request)
    {
        $bulan = $request->get('bulan');
        $totalOmset = \App\Models\HRD\PrOmsetBulanan::where('bulan', $bulan)->sum('nominal');
        return response()->json(['total_omset' => number_format($totalOmset, 2)]);
    }

    public function index()
    {
        return view('hrd.payroll.slip_gaji.index');
    }

        public function storeAll(Request $request)
    {
        $bulan = $request->input('bulan');
        $omsetBulanan = $request->input('omset_bulanan', []);

        // Save omset bulanan data first
        $totalOmset = 0;
        foreach ($omsetBulanan as $insentifOmsetId => $nominal) {
            \App\Models\HRD\PrOmsetBulanan::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'insentif_omset_id' => $insentifOmsetId
                ],
                [
                    'nominal' => $nominal
                ]
            );
            $totalOmset += floatval($nominal);
        }

        // Then create slip gaji records for all employees
        $employees = Employee::all();
        foreach ($employees as $employee) {
            PrSlipGaji::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'bulan' => $bulan
                ],
                [
                    'status_gaji' => 'draft',
                    // You can add more fields here if needed
                ]
            );
        }
        return response()->json(['success' => true, 'total_omset' => number_format($totalOmset, 2)]);
    }


    public function data(Request $request)
    {
        $query = PrSlipGaji::with(['employee.division']);
        return datatables()->of($query)
            ->addColumn('id', function($row) {
                return $row->id;
            })
            ->addColumn('no_induk', function($row) {
                return $row->employee ? $row->employee->no_induk : '';
            })
            ->addColumn('nama', function($row) {
                return $row->employee ? $row->employee->nama : '';
            })
            ->addColumn('divisi', function($row) {
                return $row->employee && $row->employee->division ? $row->employee->division->name : '';
            })
            ->addColumn('jumlah_hari_masuk', function($row) {
                return $row->total_hari_masuk;
            })
            ->addColumn('kpi_poin', function($row) {
                return $row->kpi_poin;
            })
            ->addColumn('jumlah_pendapatan', function($row) {
                return number_format($row->total_pendapatan, 2);
            })
            ->addColumn('jumlah_potongan', function($row) {
                return number_format($row->total_potongan, 2);
            })
            ->addColumn('total_gaji', function($row) {
                return number_format($row->total_gaji, 2);
            })
            ->addColumn('status', function($row) {
                return $row->status_gaji;
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-info btn-sm btn-detail">Detail Slip</button> <button class="btn btn-warning btn-sm btn-status">Change Status</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function detail($id)
    {
        $slip = PrSlipGaji::with(['employee.division'])->findOrFail($id);
        return view('hrd.payroll.slip_gaji._detail', compact('slip'))->render();
    }

    public function changeStatus(Request $request, $id)
    {
    $slip = PrSlipGaji::findOrFail($id);
    $slip->status_gaji = $request->input('status_gaji', $slip->status_gaji == 'draft' ? 'final' : 'draft');
    $slip->save();
    return response()->json(['success' => true]);
    }
}
