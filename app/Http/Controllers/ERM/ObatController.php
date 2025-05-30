<?php

namespace App\Http\Controllers\ERM;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ERM\Obat;
use App\Models\ERM\Supplier;
use App\Models\ERM\ZatAktif;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ObatController extends Controller
{
    public function index()
    {
        $obats = Obat::with('zatAktifs')->get();
        return view('erm.obat.index', compact('obats'));
    }

    public function create()
    {
        $zatAktif = ZatAktif::all();
        $supplier = Supplier::all();
        return view('erm.obat.create', compact('zatAktif', 'supplier'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'zataktif_id' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            // Dapatkan ID terbesar saat ini dengan lock
            $lastId = DB::table('erm_obats')
                ->select(DB::raw('MAX(CAST(id AS UNSIGNED)) as max_id'))
                ->lockForUpdate()
                ->value('max_id');

            $newId = $lastId ? str_pad((int)$lastId + 1, 6, '0', STR_PAD_LEFT) : '000001';

            // Insert Obat
            $obat = Obat::create([
                'id' => $newId,
                'nama' => $request->nama,
                'dosis' => $request->dosis,
                'satuan' => $request->satuan,
                'harga_net' => $request->harga_net,
                'harga_fornas' => $request->harga_fornas,
                'harga_nonfornas' => $request->harga_nonfornas,

                'stok' => $request->stok,
                // tambahkan field lain jika perlu
            ]);

            // Hubungkan dengan zat aktif
            $obat->zatAktifs()->attach($request->zataktif_id);

            DB::commit();

            return redirect()->route('erm.obat.index')->with('success', 'Obat berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menambahkan obat: ' . $e->getMessage());
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        // Fetch obat data based on the search query
        $obats = Obat::where('nama', 'LIKE', "%{$query}%")
            ->orWhere('dosis', 'LIKE', "%{$query}%")
            ->orWhere('satuan', 'LIKE', "%{$query}%")
            ->get();

        // Return the data as JSON
        return response()->json($obats->map(function ($obat) {
            return [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'dosis' => $obat->dosis,
                'satuan' => $obat->satuan,
                'stok' => $obat->stok
            ];
        }));
    }
}
