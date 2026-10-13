<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\LabPaket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LabPaketController extends Controller
{
    /**
     * Paket list for the DataTable: plain data with its tests and their total harga satuan, the page renders it.
     */
    public function data(Request $request)
    {
        $query = LabPaket::query()->select('erm_lab_paket.*')->with('labTests:id,nama,harga,available')->withCount('labTests');

        // "Data yang perlu action" card filter
        \App\Support\LabDataChecks::apply($query, $request->input('action'), 'paket');
        $q = trim((string) $request->input('search.value'));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('erm_lab_paket.nama', 'like', "%{$q}%")
                    ->orWhereHas('labTests', fn ($t) => $t->where('erm_lab_test.nama', 'like', "%{$q}%"));
            });
        }

        return DataTables::eloquent($query)
            ->only(['id', 'nama', 'deskripsi', 'harga_paket', 'tests', 'jumlah_test', 'total_satuan'])
            ->addColumn('tests', fn ($row) => $row->labTests->sortBy('nama')->map(fn ($t) => ['nama' => $t->nama, 'available' => $t->available !== 'no'])->values()->all())
            ->addColumn('jumlah_test', fn ($row) => (int) $row->lab_tests_count)
            // What the tests would cost one by one, to show the paket saving
            ->addColumn('total_satuan', fn ($row) => (float) $row->labTests->sum('harga'))
            ->editColumn('harga_paket', fn ($row) => (float) $row->harga_paket)
            ->orderColumn('nama', 'erm_lab_paket.nama $1')
            ->orderColumn('harga_paket', 'erm_lab_paket.harga_paket $1')
            ->orderColumn('jumlah_test', 'lab_tests_count $1')
            // Rendered and escaped by the page
            ->escapeColumns([])
            ->make(true);
    }

    public function show($id)
    {
        $paket = LabPaket::with(['labTests.labKategori:id,nama'])->findOrFail($id);

        return response()->json($paket);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:erm_lab_paket,nama'],
            'deskripsi' => 'nullable|string',
            'harga_paket' => 'required|numeric|min:0',
            'lab_test_ids' => 'required|array|min:1',
            'lab_test_ids.*' => 'exists:erm_lab_test,id',
        ]);

        $paket = LabPaket::create([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga_paket' => $validated['harga_paket'],
        ]);

        $paket->labTests()->sync($validated['lab_test_ids']);

        return response()->json([
            'message' => 'Paket lab ditambahkan',
            'data' => $paket->load('labTests'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $paket = LabPaket::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('erm_lab_paket', 'nama')->ignore($paket->id)],
            'deskripsi' => 'nullable|string',
            'harga_paket' => 'required|numeric|min:0',
            'lab_test_ids' => 'required|array|min:1',
            'lab_test_ids.*' => 'exists:erm_lab_test,id',
        ]);

        $paket->update([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga_paket' => $validated['harga_paket'],
        ]);

        $paket->labTests()->sync($validated['lab_test_ids']);

        return response()->json([
            'message' => 'Paket lab disimpan',
            'data' => $paket->load('labTests'),
        ]);
    }

    /**
     * A paket that was ever requested or billed keeps its history (deleting would null permintaan.lab_paket_id).
     */
    public function destroy($id)
    {
        $paket = LabPaket::findOrFail($id);
        $usage = collect([
            'permintaan lab' => $paket->labPermintaans()->count(),
            'billing' => $paket->billings()->count(),
        ])->filter()->map(fn ($n, $label) => "{$n} {$label}")->values();
        if ($usage->isNotEmpty()) {
            return response()->json(['message' => 'Paket lab sudah dipakai di ' . $usage->implode(', ') . ', jadi tidak bisa dihapus agar riwayat pasien tetap utuh.'], 422);
        }

        $paket->labTests()->detach();
        $paket->delete();

        return response()->json(['message' => 'Paket lab dihapus']);
    }
}