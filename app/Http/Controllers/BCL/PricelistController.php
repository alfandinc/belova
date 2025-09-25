<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\Pricelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricelistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pricelist = Pricelist::join('bcl_room_category as room_category', 'bcl_pricelist.room_category', '=', 'room_category.id_category')
            ->select('bcl_pricelist.*', 'room_category.category_name as category_name', 'room_category.id_category as category_id')->get();
        $category = DB::table('bcl_room_category')->get();
        $pl_tambahan = extra_pricelist::all();
        return view('bcl.pricelist.pricelist')
            ->with('pricelist', $pricelist)
            ->with('categories', $category)
            ->with('pl_tambahan', $pl_tambahan);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'tipe_kamar'     => 'required|numeric',
                'harga'     => 'required|numeric',
                'jangka_waktu' => 'required|numeric',
                'jangka_sewa' => 'required',
            ]);
            $result = Pricelist::create([
                'price'     => $request->harga,
                'jangka_waktu'     => $request->jangka_waktu,
                'jangka_sewa'   => $request->jangka_sewa,
                'bonus_waktu'   => $request->bonus_waktu,
                'bonus_sewa' => $request->bonus_sewa,
                'room_category' => $request->tipe_kamar,
            ]);
            if (!$result) {
                return redirect()->route('bcl.pricelist.index')->with(['error' => 'Gagal menambahkan Daftar Harga!']);
            } else {
                return redirect()->route('bcl.pricelist.index')->with(['success' => 'Data Harga berhasil ditambahkan!']);
            }
            return response()->json($request);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.pricelist.index')->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pricelist $pricelist, Request $request)
    {
    }

    public function get_room_pricelist(Pricelist $pricelist, Request $request)
    {
        $pricelist = Pricelist::where('room_category', $request->id)->get();
        return response()->json($pricelist);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pricelist $pricelist, Request $request)
    {
        try {
            $pl = Pricelist::find($request->id);
            return response()->json($pl);
        } catch (\Throwable $th) {
            return redirect()->route('pricelist')->with(['error' => 'Data tidak ditemukan!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pricelist $pricelist)
    {
        try {
            $pl = Pricelist::findorfail($request->id_pricelist);
            $this->validate($request, [
                'tipe_kamar'     => 'required|numeric',
                'harga'     => 'required|numeric',
                'jangka_waktu' => 'required|numeric',
                'jangka_sewa' => 'required',
            ]);
            $result = $pl->update([
                'price'     => $request->harga,
                'jangka_waktu'     => $request->jangka_waktu,
                'jangka_sewa'   => $request->jangka_sewa,
                'bonus_waktu'   => $request->bonus_waktu,
                'bonus_sewa' => $request->bonus_sewa,
                'room_category' => $request->tipe_kamar,
            ]);
            if (!$result) {
                return redirect()->route('pricelist.index')->with(['error' => 'Gagal merubah Daftar Harga!']);
            } else {
                return redirect()->route('pricelist.index')->with(['success' => 'Data Harga berhasil dirubah!']);
            }
        } catch (\Throwable $th) {
            return redirect()->route('pricelist.index')->with(['error' => 'Data tidak ditemukan!']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pricelist $pricelist, Request $request)
    {
        try {
            $pl = Pricelist::find($request->id);
            $pl->delete();
            return redirect()->route('pricelist.index')->with(['success' => 'Data Harga berhasil dihapus!']);
        } catch (\Throwable $th) {
            return redirect()->route('pricelist.index')->with(['error' => 'Data tidak ditemukan!']);
        }
    }
}
