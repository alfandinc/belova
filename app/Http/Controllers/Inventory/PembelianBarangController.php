<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\PembelianBarang;
use App\Models\Inventory\Barang;
use App\Models\Inventory\Gedung;
use App\Models\Inventory\StokBarang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class PembelianBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PembelianBarang::with(['barang', 'gedung'])->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('barang', function($row){
                    return $row->barang ? $row->barang->name : 'N/A';
                })
                ->addColumn('gedung', function($row){
                    return $row->gedung ? $row->gedung->name : 'N/A';
                })
                ->addColumn('total_harga', function($row){
                    return 'Rp ' . number_format($row->jumlah * $row->harga_satuan, 0, ',', '.');
                })
                ->addColumn('tanggal', function($row){
                    return date('d-m-Y', strtotime($row->tanggal_pembelian));
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="javascript:void(0)" data-id="'.$row->id.'" class="edit btn btn-success btn-sm">Edit</a> <a href="javascript:void(0)" data-id="'.$row->id.'" class="delete btn btn-danger btn-sm">Delete</a>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->order(function ($query) {
                    $query->orderBy('created_at', 'desc');
                })
                ->make(true);
        }
        
        $barangs = Barang::all();
        $gedungs = Gedung::all();
        
        return view('inventory.pembelian_barang.index', compact('barangs', 'gedungs'));
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
            'barang_id' => 'required|exists:inv_barang,id',
            'gedung_id' => 'required|exists:inv_gedung,id',
            'dibeli_dari' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'tanggal_pembelian' => 'required|date',
            'no_faktur' => 'required|string|max:100',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $pembelianBarang = PembelianBarang::updateOrCreate(
                ['id' => $request->pembelian_id],
                [
                    'barang_id' => $request->barang_id,
                    'gedung_id' => $request->gedung_id,
                    'dibeli_dari' => $request->dibeli_dari,
                    'jumlah' => $request->jumlah,
                    'tanggal_pembelian' => $request->tanggal_pembelian,
                    'no_faktur' => $request->no_faktur,
                    'harga_satuan' => $request->harga_satuan,
                ]
            );

            // Update stock if it's a new purchase or if editing with a different quantity
            if (!$request->pembelian_id) {
                $stokBarang = StokBarang::where('barang_id', $request->barang_id)->first();
                
                if ($stokBarang) {
                    $stokBarang->jumlah += $request->jumlah;
                    $stokBarang->save();
                } else {
                    StokBarang::create([
                        'barang_id' => $request->barang_id,
                        'jumlah' => $request->jumlah,
                    ]);
                }
            } else {
                $oldPurchase = PembelianBarang::find($request->pembelian_id);
                if ($oldPurchase && $oldPurchase->jumlah != $request->jumlah) {
                    $stokBarang = StokBarang::where('barang_id', $request->barang_id)->first();
                    if ($stokBarang) {
                        // Adjust the stock by the difference
                        $stokBarang->jumlah += ($request->jumlah - $oldPurchase->jumlah);
                        $stokBarang->save();
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembelian Barang saved successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to save pembelian barang: ' . $e->getMessage()]);
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
        $pembelianBarang = PembelianBarang::findOrFail($id);
        return response()->json($pembelianBarang);
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
            $pembelian = PembelianBarang::findOrFail($id);
            
            // Reduce the stock
            $stokBarang = StokBarang::where('barang_id', $pembelian->barang_id)->first();
            if ($stokBarang) {
                $stokBarang->jumlah -= $pembelian->jumlah;
                // Ensure stock doesn't go below zero
                if ($stokBarang->jumlah < 0) {
                    $stokBarang->jumlah = 0;
                }
                $stokBarang->save();
            }
            
            // Delete the purchase record
            $pembelian->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembelian Barang deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Failed to delete pembelian barang: ' . $e->getMessage()]);
        }
    }
}
