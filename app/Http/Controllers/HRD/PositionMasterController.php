<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Position;
use App\Models\HRD\Division;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class PositionMasterController extends Controller
{
    public function index()
    {
        $divisions = Division::all();
        return view('hrd.master.position.index', compact('divisions'));
    }

    public function getData()
    {
        $positions = Position::with(['employees', 'division']);

        return DataTables::of($positions)
            ->addColumn('division_name', function ($position) {
                return $position->division->name ?? '-';
            })
            ->addColumn('employee_count', function ($position) {
                return $position->employees->count();
            })
            ->addColumn('action', function ($position) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-position" data-id="'.$position->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-position" data-id="'.$position->id.'">
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'division_id' => 'required|exists:hrd_division,id'
        ]);

        $position = Position::create([
            'name' => $request->name,
            'description' => $request->description,
            'division_id' => $request->division_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil ditambahkan',
            'data' => $position
        ]);
    }

    public function show($id)
    {
        $position = Position::findOrFail($id);
        return response()->json($position);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'division_id' => 'required|exists:hrd_division,id'
        ]);

        $position->update([
            'name' => $request->name,
            'description' => $request->description,
            'division_id' => $request->division_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil diperbarui',
            'data' => $position
        ]);
    }

    public function destroy($id)
    {
        $position = Position::findOrFail($id);
        
        // Check if there are any employees with this position
        if ($position->employees()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus jabatan karena masih digunakan oleh karyawan'
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Posisi/Jabatan berhasil dihapus'
        ]);
    }
}
