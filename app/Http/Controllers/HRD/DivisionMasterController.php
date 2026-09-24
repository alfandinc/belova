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
        $positions = Position::with('divisions')->orderBy('name')->get();
        $levelOptions = Position::LEVEL_OPTIONS;
        $divisionOptions = $divisions->map(function (Division $division) {
            return [
                'id' => $division->id,
                'name' => $division->name,
            ];
        })->values()->all();
        $parentPositionOptions = $positions->map(function (Position $position) {
            return [
                'id' => $position->id,
                'name' => $position->name,
                'level' => $position->level,
                'division_ids' => $position->division_ids,
                'division_names' => $position->division_names,
            ];
        })->values()->all();

        return view('hrd.master.division.index', compact('divisions', 'positions', 'levelOptions', 'divisionOptions', 'parentPositionOptions'));
    }

    public function getData()
    {
        $divisions = Division::query();

        return DataTables::of($divisions)
            ->addColumn('status_badge', function ($division) {
                return $division->is_active
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Nonaktif</span>';
            })
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
                ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:hrd_division,name',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        $division = Division::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => (bool) $request->is_active,
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
            'description' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        $division->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => (bool) $request->is_active,
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
