<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\Gudang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GudangMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $mappings = GudangMapping::with('gudang')->get();
            
            return DataTables::of($mappings)
                ->addColumn('transaction_type_label', function ($row) {
                    $types = GudangMapping::getTransactionTypes();
                    return $types[$row->transaction_type] ?? $row->transaction_type;
                })
                ->addColumn('gudang_nama', function ($row) {
                    return $row->gudang ? $row->gudang->nama : '-';
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge badge-success">Aktif</span>';
                    }
                    return '<span class="badge badge-secondary">Tidak Aktif</span>';
                })
                ->addColumn('aksi', function ($row) {
                    $editBtn = '<button type="button" class="btn btn-sm btn-warning" onclick="editMapping(' . $row->id . ')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-danger ml-1" onclick="deleteMapping(' . $row->id . ')">
                                    <i class="fas fa-trash"></i> Hapus
                                  </button>';
                    
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status', 'aksi'])
                ->make(true);
        }

        $gudangs = Gudang::orderBy('nama')->get();
        $transactionTypes = GudangMapping::getTransactionTypes();

        return view('erm.gudang-mapping.index', compact('gudangs', 'transactionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validTransactionTypes = implode(',', array_keys(GudangMapping::getTransactionTypes()));
        
        $request->validate([
            'transaction_type' => 'required|string|in:' . $validTransactionTypes,
            'gudang_id' => 'required|exists:erm_gudang,id',
        ]);

        try {
            GudangMapping::setActiveMapping(
                $request->transaction_type,
                $request->gudang_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Mapping gudang berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $mapping = GudangMapping::with('gudang')->findOrFail($id);
        return response()->json($mapping);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validTransactionTypes = implode(',', array_keys(GudangMapping::getTransactionTypes()));
        
        $request->validate([
            'transaction_type' => 'required|string|in:' . $validTransactionTypes,
            'gudang_id' => 'required|exists:erm_gudang,id',
        ]);

        try {
            $mapping = GudangMapping::findOrFail($id);
            
            // If we're making this active, deactivate others of same type
            if ($request->is_active) {
                GudangMapping::where('transaction_type', $request->transaction_type)
                             ->where('id', '!=', $id)
                             ->update(['is_active' => false]);
            }

            $mapping->update([
                'transaction_type' => $request->transaction_type,
                'gudang_id' => $request->gudang_id,
                'is_active' => $request->is_active ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mapping gudang berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $mapping = GudangMapping::findOrFail($id);
            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mapping gudang berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active mappings for dropdown
     */
    public function getActiveMappings()
    {
        $mappings = GudangMapping::where('is_active', true)
                                 ->with('gudang')
                                 ->get()
                                 ->keyBy('transaction_type');

        return response()->json([
            'resep' => $mappings['resep'] ?? null,
            'tindakan' => $mappings['tindakan'] ?? null,
        ]);
    }

    /**
     * Get default gudang for transaction type
     */
    public function getDefaultGudang($transactionType)
    {
        $mapping = GudangMapping::getActiveMapping($transactionType);
        
        if ($mapping) {
            return response()->json([
                'success' => true,
                'gudang' => [
                    'id' => $mapping->gudang_id,
                    'nama' => $mapping->gudang->nama
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada mapping aktif untuk transaksi: ' . $transactionType
        ]);
    }
}
