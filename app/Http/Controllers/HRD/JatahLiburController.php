<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class JatahLiburController extends Controller
{
    public function index()
    {
        return view('hrd.master.jatah-libur.index');
    }

    public function getData()
    {
        $jatahLibur = JatahLibur::with('employee');

        return DataTables::of($jatahLibur)
            ->addColumn('employee_name', function ($jatah) {
                return $jatah->employee->nama ?? 'N/A';
            })
            ->addColumn('employee_number', function ($jatah) {
                return $jatah->employee->no_induk ?? 'N/A';
            })
            ->addColumn('division', function ($jatah) {
                return $jatah->employee->division->name ?? 'N/A';
            })
            ->addColumn('action', function ($jatah) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-jatah-libur" data-id="'.$jatah->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hrd_employee,id|unique:hrd_jatah_libur,employee_id',
            'jatah_cuti_tahunan' => 'required|integer|min:0',
            'jatah_ganti_libur' => 'required|integer|min:0'
        ]);

        $jatahLibur = JatahLibur::create([
            'employee_id' => $request->employee_id,
            'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan,
            'jatah_ganti_libur' => $request->jatah_ganti_libur
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jatah libur berhasil ditambahkan',
            'data' => $jatahLibur
        ]);
    }

    public function show($id)
    {
        $jatahLibur = JatahLibur::findOrFail($id);
        return response()->json($jatahLibur);
    }

    public function update(Request $request, $id)
    {
        $jatahLibur = JatahLibur::findOrFail($id);

        $request->validate([
            'jatah_cuti_tahunan' => 'required|integer|min:0',
            'jatah_ganti_libur' => 'required|integer|min:0'
        ]);

        $jatahLibur->update([
            'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan,
            'jatah_ganti_libur' => $request->jatah_ganti_libur
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jatah libur berhasil diperbarui',
            'data' => $jatahLibur
        ]);
    }

    public function getEmployeesWithoutJatahLibur()
    {
        try {
            // Debug information
            Log::info('Starting getEmployeesWithoutJatahLibur method');
            
            // Get all employee IDs that already have jatah libur
            $employeesWithJatahLibur = JatahLibur::pluck('employee_id')->toArray();
            Log::info('Employees with jatah libur: ' . count($employeesWithJatahLibur));
            
            // Check if we have any employees at all
            $allEmployeeCount = Employee::count();
            Log::info('Total employee count: ' . $allEmployeeCount);
            
            // Query employees without jatah libur
            $query = Employee::query();
            
            // Only filter by not in if we have any employees with jatah libur
            if (!empty($employeesWithJatahLibur)) {
                $query->whereNotIn('id', $employeesWithJatahLibur);
            }
            
            // Add necessary fields with proper aliases
            $employees = $query->select(
                'id',
                'nama as name',
                DB::raw('COALESCE(no_induk, CONCAT("EMP", id)) as employee_number')
            )->get();
            
            Log::info('Employees without jatah libur: ' . $employees->count());
            
            // Generate some test data if no employees are found
            if ($employees->isEmpty() && $allEmployeeCount === 0) {
                Log::info('No employees found, returning dummy data for testing');
                
                // Return at least one dummy employee for testing
                return response()->json([
                    [
                        'id' => 999,
                        'name' => 'Dummy Employee (No employees in system)',
                        'employee_number' => 'EMP999'
                    ]
                ]);
            }
            
            return response()->json($employees);
            
        } catch (\Exception $e) {
            Log::error('Error in getEmployeesWithoutJatahLibur: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
