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
        // Build a query that selects jatah libur plus employee fields so DataTables can
        // search and order by employee name or number. We join the hrd_employee table
        // and left join divisions (if available) to include division name.
        $jatahLiburQuery = JatahLibur::select([
            'hrd_jatah_libur.*',
            'e.nama as employee_name',
            'e.no_induk as employee_number',
            'd.name as division'
        ])
        ->from('hrd_jatah_libur')
        ->leftJoin('hrd_employee as e', 'hrd_jatah_libur.employee_id', '=', 'e.id')
        ->leftJoin('hrd_division as d', 'e.division_id', '=', 'd.id');

        return DataTables::of($jatahLiburQuery)
            // If client searches for 'employee_name' or 'employee_number', let DataTables
            // know how to filter those columns via the column names used in the select.
            ->filterColumn('employee_name', function ($query, $keyword) {
                $sql = "e.nama like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('employee_number', function ($query, $keyword) {
                $sql = "e.no_induk like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('employee_name', function ($jatah) {
                return $jatah->employee_name ?? 'N/A';
            })
            ->addColumn('employee_number', function ($jatah) {
                return $jatah->employee_number ?? 'N/A';
            })
            ->addColumn('division', function ($jatah) {
                return $jatah->division ?? 'N/A';
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
