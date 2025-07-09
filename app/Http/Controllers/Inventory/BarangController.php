<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Barang;
use App\Models\Inventory\StokBarang;
use App\Models\Inventory\TipeBarang;
use App\Models\Inventory\Ruangan;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Barang::with(['tipeBarang', 'ruangan', 'stokBarang']);
            // Filter by gedung if provided
            if ($request->filled('gedung_id')) {
                $data = $data->whereHas('ruangan', function($q) use ($request) {
                    $q->where('gedung_id', $request->gedung_id);
                });
            }
            // Filter by ruangan if provided
            if ($request->filled('ruangan_id')) {
                $data = $data->where('ruangan_id', $request->ruangan_id);
            }
            // Filter by tipe_barang_id if provided
            if ($request->filled('tipe_barang_id')) {
                $data = $data->where('tipe_barang_id', $request->tipe_barang_id);
            }
            $data = $data->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tipe_barang', function($row){
                    return $row->tipeBarang ? $row->tipeBarang->name : 'N/A';
                })
                ->addColumn('ruangan', function($row){
                    return $row->ruangan ? $row->ruangan->name : 'N/A';
                })
                ->addColumn('stok', function($row){
                    return $row->stokBarang ? $row->stokBarang->jumlah : '0';
                })
                ->addColumn('under_maintenance', function($row){
                    return $row->maintenanceBarang && $row->maintenanceBarang->count() > 0 ? true : false;
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="javascript:void(0)" data-id="'.$row->id.'" class="edit btn btn-success btn-sm">Edit</a> <a href="javascript:void(0)" data-id="'.$row->id.'" class="delete btn btn-danger btn-sm">Delete</a> <a href="javascript:void(0)" data-id="'.$row->id.'" class="editStok btn btn-info btn-sm">Stok</a>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->order(function ($query) {
                    $query->orderBy('created_at', 'desc');
                })
                ->make(true);
        }
        
        $tipeBarangs = TipeBarang::all();
        $ruangans = Ruangan::with('gedung')->get();
        $gedungs = \App\Models\Inventory\Gedung::all();
        
        return view('inventory.barang.index', compact('tipeBarangs', 'ruangans', 'gedungs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tipe_barang_id' => 'required|exists:inv_tipe_barang,id',
            'ruangan_id' => 'required|exists:inv_ruangan,id',
            'kode' => 'required|string|max:50',
            'satuan' => 'required|string|max:50',
            'merk' => 'nullable|string|max:100',
            'spec' => 'nullable|string|max:500',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'stok' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $barang = Barang::updateOrCreate(
                ['id' => $request->barang_id],
                [
                    'name' => $request->name,
                    'tipe_barang_id' => $request->tipe_barang_id,
                    'ruangan_id' => $request->ruangan_id,
                    'kode' => $request->kode,
                    'satuan' => $request->satuan,
                    'merk' => $request->merk,
                    'spec' => $request->spec,
                    'depreciation_rate' => $request->depreciation_rate,
                ]
            );

            if ($request->barang_id) {
                $stokBarang = StokBarang::where('barang_id', $request->barang_id)->first();
                if ($stokBarang) {
                    $stokBarang->jumlah = $request->stok;
                    $stokBarang->save();
                } else {
                    StokBarang::create([
                        'barang_id' => $barang->id,
                        'jumlah' => $request->stok,
                    ]);
                }
            } else {
                StokBarang::create([
                    'barang_id' => $barang->id,
                    'jumlah' => $request->stok,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Barang saved successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to save barang: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $barang = Barang::with('stokBarang')->findOrFail($id);
        return response()->json($barang);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $barang = Barang::findOrFail($id);
            
            // Delete associated stock
            StokBarang::where('barang_id', $id)->delete();
            
            // Delete the item
            $barang->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Barang deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to delete barang: ' . $e->getMessage()]);
        }
    }

    /**
     * Update stock quantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateStok(Request $request)
    {
        $request->validate([
            'barang_id' => 'required|exists:inv_barang,id',
            'jumlah' => 'required|integer|min:0',
        ]);

        try {
            $stokBarang = StokBarang::where('barang_id', $request->barang_id)->first();
            
            if ($stokBarang) {
                $stokBarang->jumlah = $request->jumlah;
                $stokBarang->save();
            } else {
                StokBarang::create([
                    'barang_id' => $request->barang_id,
                    'jumlah' => $request->jumlah,
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Stok updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update stok: ' . $e->getMessage()]);
        }
    }

    // Add endpoint for AJAX ruangan by gedung
    public function getRuanganByGedung($gedungId)
    {
        $ruangans = \App\Models\Inventory\Ruangan::where('gedung_id', $gedungId)->select('id', 'name')->get();
        return response()->json($ruangans);
    }
}
