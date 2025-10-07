<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabKategori;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LabKategoriController extends Controller
{
    public function data(Request $request)
    {
        $query = LabKategori::query()->withCount('labTests');
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function($row) {
                return '<button class="btn btn-sm btn-warning edit-kategori" data-id="'.$row->id.'" data-nama="'.e($row->nama).'">Edit</button> '
                    .'<button class="btn btn-sm btn-danger delete-kategori" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:erm_lab_kategori,nama'
        ]);

        $kategori = LabKategori::create($validated);
        return response()->json(['message' => 'Kategori created', 'data' => $kategori]);
    }

    public function update(Request $request, $id)
    {
        $kategori = LabKategori::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:erm_lab_kategori,nama,'.$kategori->id
        ]);
        $kategori->update($validated);
        return response()->json(['message' => 'Kategori updated', 'data' => $kategori]);
    }

    public function destroy($id)
    {
        $kategori = LabKategori::withCount('labTests')->findOrFail($id);
        if ($kategori->lab_tests_count > 0) {
            return response()->json(['message' => 'Tidak bisa hapus: masih ada Lab Test terkait'], 409);
        }
        $kategori->delete();
        return response()->json(['message' => 'Kategori deleted']);
    }
}
