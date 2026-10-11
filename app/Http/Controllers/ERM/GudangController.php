<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\ObatStokGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Master gudang lives on the admin "Master Gudang & Mapping" page, next to the gudang mapping
        return redirect()->route('admin.obat_gudang_mapping.index');
    }

    /**
     * Get data for DataTables
     */
    public function data()
    {
        $gudangs = Gudang::select(['id', 'nama', 'lokasi', 'created_at']);

        // Active mappings per gudang, so the admin sees what each gudang is used for
        $transactionTypes = GudangMapping::getTransactionTypes();
        $mappingsPerGudang = GudangMapping::where('is_active', true)
            ->get(['gudang_id', 'transaction_type', 'entity_type', 'secondary_entity_type'])
            ->groupBy('gudang_id');
        $stokPerGudang = ObatStokGudang::where('stok', '>', 0)
            ->select('gudang_id', DB::raw('COUNT(DISTINCT obat_id) as jumlah_obat'))
            ->groupBy('gudang_id')
            ->pluck('jumlah_obat', 'gudang_id');

        return DataTables::of($gudangs)
            ->addColumn('mapping', function ($gudang) use ($mappingsPerGudang, $transactionTypes) {
                $rows = $mappingsPerGudang->get($gudang->id);
                if (!$rows) {
                    return '<span class="text-muted">-</span>';
                }
                return $rows->map(function ($m) use ($transactionTypes) {
                    $label = $transactionTypes[$m->transaction_type] ?? $m->transaction_type;
                    $scoped = $m->entity_type || $m->secondary_entity_type;
                    $class = $m->transaction_type === 'expired' ? 'badge-danger' : 'badge-info';
                    return '<span class="badge ' . $class . ' mr-1">' . e($label) . ($scoped ? ' (khusus)' : '') . '</span>';
                })->implode('');
            })
            ->addColumn('jumlah_obat', function ($gudang) use ($stokPerGudang) {
                return (int) ($stokPerGudang[$gudang->id] ?? 0);
            })
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
            ->rawColumns(['action', 'mapping'])
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
            // Several foreign keys cascade (stok rows, gudang mapping, retur items) and kartu stok has none,
            // so a gudang that was ever used must never be deleted: that would erase or orphan history.
            $usages = array_filter([
                'data stok obat' => ObatStokGudang::withTrashed()->where('gudang_id', $gudang->id)->exists(),
                'riwayat kartu stok' => DB::table('erm_kartu_stok')->where('gudang_id', $gudang->id)->exists(),
                'mapping gudang' => GudangMapping::where('gudang_id', $gudang->id)->exists(),
                'faktur pembelian' => DB::table('erm_fakturbeli_items')->where('gudang_id', $gudang->id)->exists(),
                'retur pembelian' => DB::table('erm_fakturretur_items')->where('gudang_id', $gudang->id)->exists(),
                'mutasi gudang' => DB::table('erm_mutasi_gudang')->where('gudang_asal_id', $gudang->id)->orWhere('gudang_tujuan_id', $gudang->id)->exists(),
                'mutasi stok' => DB::table('erm_mutasi_stok')->where('gudang_id', $gudang->id)->exists(),
                'stok opname' => DB::table('erm_stok_opname')->where('gudang_id', $gudang->id)->exists(),
                'obat hibah' => DB::table('erm_obat_hibah_items')->where('gudang_id', $gudang->id)->exists(),
            ]);

            if ($usages) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gudang tidak dapat dihapus karena sudah dipakai di: ' . implode(', ', array_keys($usages))
                        . '. Ganti nama gudang bila perlu, riwayatnya tetap aman.'
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
