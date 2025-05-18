<?php

namespace App\Http\Controllers\ERM;

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

        $obat = Obat::create([
            'id' => (string) Str::uuid(),
            'nama' => $request->nama,
            'dosis' => $request->dosis,
            'satuan' => $request->satuan,
            'harga_umum' => $request->harga_umum,
            'harga_inhealth' => $request->harga_inhealth,
            'stok' => $request->stok,
            // 'supplier' => $request->supplier,


        ]);
        $obat->zatAktifs()->attach($request->zataktif_id);

        return redirect()->route('erm.obat.index')->with('success', 'Obat berhasil ditambahkan');
    }
}
