<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabKategori;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LabKategoriController extends Controller
{
    /**
     * Kategori list for the DataTable (plain data, the page renders it).
     */
    public function data(Request $request)
    {
        $query = LabKategori::query()->select('erm_lab_kategori.*')->withCount('labTests');

        // "Data yang perlu action" card filter
        \App\Support\LabDataChecks::apply($query, $request->input('action'), 'kategori');
        $q = trim((string) $request->input('search.value'));
        if ($q !== '') {
            $query->where('erm_lab_kategori.nama', 'like', "%{$q}%");
        }

        return DataTables::eloquent($query)
            ->only(['id', 'nama', 'lab_tests_count'])
            ->orderColumn('nama', 'erm_lab_kategori.nama $1')
            ->orderColumn('lab_tests_count', 'lab_tests_count $1')
            // Rendered and escaped by the page
            ->escapeColumns([])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:erm_lab_kategori,nama'
        ]);

        $kategori = LabKategori::create($validated);
        return response()->json(['message' => 'Kategori ditambahkan', 'data' => $kategori]);
    }

    public function update(Request $request, $id)
    {
        $kategori = LabKategori::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:erm_lab_kategori,nama,'.$kategori->id
        ]);
        $kategori->update($validated);
        return response()->json(['message' => 'Kategori disimpan', 'data' => $kategori]);
    }

    public function destroy($id)
    {
        $kategori = LabKategori::withCount('labTests')->findOrFail($id);
        if ($kategori->lab_tests_count > 0) {
            return response()->json(['message' => 'Kategori masih dipakai ' . $kategori->lab_tests_count . ' lab test. Pindahkan test-nya ke kategori lain dulu.'], 409);
        }
        $kategori->delete();
        return response()->json(['message' => 'Kategori dihapus']);
    }
}
