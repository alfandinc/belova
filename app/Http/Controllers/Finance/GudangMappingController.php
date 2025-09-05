<?php

namespace App\Http\Controllers\Finance;

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
    public function index()
    {
        return view('finance.gudang-mapping.index');
    }

    /**
     * Get data for DataTables
     */
    public function data()
    {
        $mappings = GudangMapping::with('gudang')
            ->select(['id', 'transaction_type', 'gudang_id', 'is_active', 'created_at']);

        return DataTables::of($mappings)
            ->addColumn('gudang_name', function ($mapping) {
                return $mapping->gudang ? $mapping->gudang->nama : '-';
            })
            ->addColumn('status', function ($mapping) {
                return $mapping->is_active 
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('transaction_type_label', function ($mapping) {
                return ucfirst($mapping->transaction_type);
            })
            ->addColumn('actions', function ($mapping) {
                return '
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary edit-mapping" 
                                data-id="' . $mapping->id . '" 
                                data-transaction-type="' . $mapping->transaction_type . '"
                                data-gudang-id="' . $mapping->gudang_id . '"
                                data-is-active="' . ($mapping->is_active ? '1' : '0') . '">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-mapping" 
                                data-id="' . $mapping->id . '">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_type' => 'required|in:resep,tindakan',
            'gudang_id' => 'required|exists:erm_gudangs,id',
            'is_active' => 'boolean',
        ]);

        // If setting as active, deactivate others of same type
        if ($request->is_active) {
            GudangMapping::where('transaction_type', $request->transaction_type)
                ->update(['is_active' => false]);
        }

        $mapping = GudangMapping::create([
            'transaction_type' => $request->transaction_type,
            'gudang_id' => $request->gudang_id,
            'is_active' => $request->is_active ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gudang mapping berhasil dibuat',
            'data' => $mapping->load('gudang')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $mapping = GudangMapping::findOrFail($id);

        $request->validate([
            'transaction_type' => 'required|in:resep,tindakan',
            'gudang_id' => 'required|exists:erm_gudangs,id',
            'is_active' => 'boolean',
        ]);

        // If setting as active, deactivate others of same type
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
            'message' => 'Gudang mapping berhasil diperbarui',
            'data' => $mapping->load('gudang')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $mapping = GudangMapping::findOrFail($id);
        $mapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gudang mapping berhasil dihapus'
        ]);
    }

    /**
     * Get active mappings
     */
    public function getActiveMappings()
    {
        $mappings = collect();
        
        foreach (['resep', 'tindakan'] as $type) {
            $mapping = GudangMapping::getActiveMapping($type);
            if ($mapping) {
                $mappings->put($type, $mapping);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $mappings
        ]);
    }

    /**
     * Get default gudang for transaction type
     */
    public function getDefaultGudang($type)
    {
        $gudangId = GudangMapping::getDefaultGudangId($type);
        $gudang = null;
        
        if ($gudangId) {
            $gudang = Gudang::find($gudangId);
        }

        return response()->json([
            'success' => true,
            'gudang_id' => $gudangId,
            'gudang' => $gudang
        ]);
    }
}
