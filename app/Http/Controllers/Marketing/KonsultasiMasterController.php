<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Konsultasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KonsultasiMasterController extends Controller
{
    public function index()
    {
        $konsultasis = Konsultasi::orderBy('kategori')->orderBy('nama')->get();

        return view('marketing.konsultasi.index', compact('konsultasis'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:erm_konsultasi,nama'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        $konsultasi = Konsultasi::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil ditambahkan.',
            'data' => $konsultasi,
        ]);
    }

    public function update(Request $request, Konsultasi $konsultasi): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('erm_konsultasi', 'nama')->ignore($konsultasi->id)],
            'kategori' => ['nullable', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        $konsultasi->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil diperbarui.',
            'data' => $konsultasi,
        ]);
    }

    public function destroy(Konsultasi $konsultasi): JsonResponse
    {
        $konsultasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil dihapus.',
        ]);
    }
}