<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabTest;
use App\Models\ERM\LabKategori;
use Yajra\DataTables\Facades\DataTables;

class LabTestController extends Controller
{
    public function index(Request $request)
    {
        // returns blade view containing DataTables for tests & categories
        return view('erm.elab.master');
    }

    public function data(Request $request)
    {
        $query = LabTest::query()->with('labKategori:id,nama');
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kategori', function($row){ return $row->labKategori?->nama; })
            ->addColumn('actions', function($row){
                return '<button class="btn btn-sm btn-warning edit-test" data-id="'.$row->id.'" data-nama="'.e($row->nama).'" data-harga="'.$row->harga.'" data-lab_kategori_id="'.$row->lab_kategori_id.'">Edit</button> '
                    .'<button class="btn btn-sm btn-danger delete-test" data-id="'.$row->id.'">Delete</button>';
            })
            ->editColumn('harga', function($row){ return $row->harga; })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'lab_kategori_id' => 'required|exists:erm_lab_kategori,id',
            'harga' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string'
        ]);

        // uniqueness per kategori (nama + kategori)
        $exists = LabTest::where('lab_kategori_id', $validated['lab_kategori_id'])
            ->where('nama', $validated['nama'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Nama test sudah ada di kategori ini'], 422);
        }
        $test = LabTest::create($validated);
        return response()->json(['message' => 'Lab test created', 'data' => $test]);
    }

    public function update(Request $request, $id)
    {
        $test = LabTest::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'lab_kategori_id' => 'required|exists:erm_lab_kategori,id',
            'harga' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string'
        ]);
        $exists = LabTest::where('lab_kategori_id', $validated['lab_kategori_id'])
            ->where('nama', $validated['nama'])
            ->where('id', '!=', $test->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Nama test sudah ada di kategori ini'], 422);
        }
        $test->update($validated);
        return response()->json(['message' => 'Lab test updated', 'data' => $test]);
    }

    public function destroy($id)
    {
        $test = LabTest::findOrFail($id);
        $test->delete();
        return response()->json(['message' => 'Lab test deleted']);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = LabTest::query();
        if ($q) {
            $query->where('nama', 'like', "%$q%");
        }
        $results = $query->limit(20)->get(['id', 'nama', 'harga']);
        return response()->json($results);
    }
}
