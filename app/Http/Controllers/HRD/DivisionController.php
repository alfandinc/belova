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

        $division = Division::with('employees')->findOrFail($employee->division);

        return view('hrd.division.my-division', compact('division'));
    }

    public function showMyTeam(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->route('hrd.dashboard')
                ->with('error', 'Data karyawan tidak ditemukan');
        }

        if ($request->ajax()) {
            $employees = Employee::where('division', $employee->division)
                ->where('id', '!=', $employee->id)
                ->with(['position', 'user']);

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
