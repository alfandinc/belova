<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\LabPaket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LabPaketController extends Controller
{
    public function data(Request $request)
    {
        $query = LabPaket::query()->with('labTests:id,nama')->withCount('labTests');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('lab_test_list', function ($row) {
                return $row->labTests->pluck('nama')->implode(', ');
            })
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-warning edit-paket" data-id="' . $row->id . '">Edit</button> '
                    . '<button class="btn btn-sm btn-danger delete-paket" data-id="' . $row->id . '">Delete</button>';
            })
            ->rawColumns(['actions'])
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
            'message' => 'Paket lab created',
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
            'message' => 'Paket lab updated',
            'data' => $paket->load('labTests'),
        ]);
    }

    public function destroy($id)
    {
        $paket = LabPaket::findOrFail($id);
        $paket->labTests()->detach();
        $paket->delete();

        return response()->json(['message' => 'Paket lab deleted']);
    }
}