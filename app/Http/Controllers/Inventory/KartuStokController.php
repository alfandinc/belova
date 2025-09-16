<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory\KartuStok;
use App\Models\Inventory\Barang;

class KartuStokController extends Controller
{
    public function index(Request $request)
    {
        $barangList = Barang::orderBy('name')->get();
        return view('inventory.kartu_stok.index', compact('barangList'));
    }

    public function data(Request $request)
    {
        $barangId = $request->query('barang_id');

        $query = KartuStok::with('barang')->orderBy('tanggal', 'desc');
        if ($barangId) {
            $query->where('barang_id', $barangId);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function detail(Request $request)
    {
        $id = $request->query('id');
        $kartu = KartuStok::with('barang')->findOrFail($id);
        return response()->json($kartu);
    }
}
