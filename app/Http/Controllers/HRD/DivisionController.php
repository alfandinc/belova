<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Division;
use App\Models\HRD\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class DivisionController extends Controller
{
    public function showMyDivision()
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        // Resolve division id from employee safely whether `division` is an id or a related model
        $divisionId = null;
        if (is_numeric($employee->division)) {
            $divisionId = $employee->division;
        } elseif ($employee->division && is_object($employee->division) && property_exists($employee->division, 'id')) {
            $divisionId = $employee->division->id;
        } elseif (isset($employee->division_id)) {
            $divisionId = $employee->division_id;
        }

        $division = Division::findOrFail($divisionId);

        // Load employees for this division via positions pivot
        $employees = Employee::active()
            ->whereHas('positions', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            })
            ->orderBy('nama')
            ->get();

        return view('hrd.division.my-division', compact('division', 'employees'));
    }

    public function showMyTeam(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        if ($request->ajax()) {
            $employees = Employee::active()
                ->whereHas('positions', function ($q) use ($divisionId) {
                    $q->where('division_id', $divisionId);
                })
                ->where('id', '!=', $employee->id)
                ->with(['positions.division', 'user']);

            return DataTables::of($employees)
                ->addColumn('status_label', function ($employee) {
                    $statusColors = [
                        'tetap' => 'success',
                        'kontrak' => 'warning',
                        'tidak aktif' => 'danger'
                    ];
                    return '<span class="badge badge-' . $statusColors[$employee->status] . '">' . ucfirst($employee->status) . '</span>';
                })
                ->addColumn('action', function ($employee) {
                    $viewBtn = '<a href="' . route('hrd.employee.show', $employee->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
                    return $viewBtn;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }

        return view('hrd.division.my-team');
    }
}
