<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Division;
use App\Models\HRD\Position;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class DivisionMasterController extends Controller
{
    public function index()
    {
        $divisions = Division::all();
        $positions = Position::all();
        return view('hrd.master.division.index', compact('divisions', 'positions'));
    }

    public function getData()
    {
        $divisions = Division::query();

        return DataTables::of($divisions)
            ->addColumn('employee_count', function ($division) {
                // Count employees by checking positions that belong to this division
                return \App\Models\HRD\Employee::whereHas('positions', function ($q) use ($division) {
                    $q->where('division_id', $division->id);
                })->count();
            })
            ->addColumn('action', function ($division) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-division" data-id="'.$division->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-division" data-id="'.$division->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:hrd_division,name',
            'description' => 'nullable|string'
        ]);

        $division = Division::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil ditambahkan',
            'data' => $division
        ]);
    }

    public function show($id)
    {
        $division = Division::findOrFail($id);
        return response()->json($division);
    }

    public function update(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('hrd_division')->ignore($division->id)
            ],
            'description' => 'nullable|string'
        ]);

        $division->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil diperbarui',
            'data' => $division
        ]);
    }

    public function destroy($id)
    {
        $division = Division::findOrFail($id);
        
        // Check if there are any related positions
        $employeeCount = \App\Models\HRD\Employee::whereHas('positions', function ($q) use ($division) {
            $q->where('division_id', $division->id);
        })->count();

        if ($employeeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus divisi karena masih memiliki karyawan'
            ], 422);
        }

        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Divisi berhasil dihapus'
        ]);
    }
}
