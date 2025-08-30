<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrSlipGaji;
use App\Models\HRD\Employee;

class PrSlipGajiController extends Controller
{
    // Update slip gaji dari modal detail
    public function update(Request $request, $id)
    {
        $slip = PrSlipGaji::findOrFail($id);
        $slip->status_gaji = $request->input('status_gaji', $slip->status_gaji);
        $slip->total_hari_masuk = $request->input('total_hari_masuk', $slip->total_hari_masuk);
        $slip->kpi_poin = $request->input('kpi_poin', $slip->kpi_poin);
        $slip->total_pendapatan = $request->input('total_pendapatan', $slip->total_pendapatan);
        $slip->total_potongan = $request->input('total_potongan', $slip->total_potongan);
        $slip->total_gaji = $request->input('total_gaji', $slip->total_gaji);
        $slip->gaji_pokok = $request->input('gaji_pokok', $slip->gaji_pokok);
        $slip->tunjangan_jabatan = $request->input('tunjangan_jabatan', $slip->tunjangan_jabatan);
        $slip->uang_makan = $request->input('uang_makan', $slip->uang_makan);
        $slip->uang_kpi = $request->input('uang_kpi', $slip->uang_kpi);
        $slip->jasa_medis = $request->input('jasa_medis', $slip->jasa_medis);
        $slip->total_jam_lembur = $request->input('total_jam_lembur', $slip->total_jam_lembur);
        $slip->uang_lembur = $request->input('uang_lembur', $slip->uang_lembur);
        $slip->potongan_pinjaman = $request->input('potongan_pinjaman', $slip->potongan_pinjaman);
        $slip->potongan_bpjs_kesehatan = $request->input('potongan_bpjs_kesehatan', $slip->potongan_bpjs_kesehatan);
        $slip->potongan_jamsostek = $request->input('potongan_jamsostek', $slip->potongan_jamsostek);
        $slip->potongan_penalty = $request->input('potongan_penalty', $slip->potongan_penalty);
        $slip->potongan_lain = $request->input('potongan_lain', $slip->potongan_lain);
        $slip->benefit_bpjs_kesehatan = $request->input('benefit_bpjs_kesehatan', $slip->benefit_bpjs_kesehatan);
        $slip->benefit_jht = $request->input('benefit_jht', $slip->benefit_jht);
        $slip->benefit_jkk = $request->input('benefit_jkk', $slip->benefit_jkk);
        $slip->benefit_jkm = $request->input('benefit_jkm', $slip->benefit_jkm);
        $slip->save();
        return response()->json(['success' => true]);
    }
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
        $bulan = $request->get('bulan');
        $query = PrSlipGaji::with(['employee.division']);
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
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
