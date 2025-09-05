<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('erm.gudang.index');
    }

    /**
     * Get data for DataTables
     */
    public function data()
    {
        $gudangs = Gudang::select(['id', 'nama', 'lokasi', 'created_at']);

        return DataTables::of($gudangs)
            ->addColumn('action', function ($gudang) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<button type="button" class="btn btn-sm btn-warning edit-btn" data-id="' . $gudang->id . '" title="Edit">';
                $actions .= '<i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $gudang->id . '" title="Hapus">';
                $actions .= '<i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->editColumn('created_at', function ($gudang) {
                return $gudang->created_at ? $gudang->created_at->format('d/m/Y H:i') : '-';
            })
            ->editColumn('lokasi', function ($gudang) {
                return $gudang->lokasi ?: '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255',
                'lokasi' => 'nullable|string|max:255'
            ]);

            $gudang = Gudang::create([
                'nama' => $request->nama,
                'lokasi' => $request->lokasi,
            ]);

            Log::info('Gudang baru dibuat', [
                'id' => $gudang->id,
                'nama' => $gudang->nama,
                'lokasi' => $gudang->lokasi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil ditambahkan',
                'data' => $gudang
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating gudang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan gudang'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Gudang $gudang)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $gudang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gudang tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gudang $gudang)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255',
                'lokasi' => 'nullable|string|max:255'
            ]);

            $gudang->update([
                'nama' => $request->nama,
                'lokasi' => $request->lokasi,
            ]);

            Log::info('Gudang diupdate', [
                'id' => $gudang->id,
                'nama' => $gudang->nama,
                'lokasi' => $gudang->lokasi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil diupdate',
                'data' => $gudang
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating gudang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate gudang'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gudang $gudang)
    {
        try {
            // Check if gudang has related stock data
            $hasStok = \App\Models\ERM\ObatStokGudang::where('gudang_id', $gudang->id)->exists();
            
            if ($hasStok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gudang tidak dapat dihapus karena masih memiliki data stok obat'
                ], 422);
            }

            $gudangNama = $gudang->nama;
            $gudang->delete();

            Log::info('Gudang dihapus', [
                'nama' => $gudangNama
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gudang berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting gudang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus gudang'
            ], 500);
        }
    }
}
