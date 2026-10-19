<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\Pricelist;
use App\Models\BCL\room_category;
use App\Services\BCL\RentalService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PricelistController extends Controller
{
    public function settings()
    {
        return redirect()->route('bcl.pricelist.index');
    }

    public function index()
    {
        $pricelist = Pricelist::join('bcl_room_category as room_category', 'bcl_pricelist.room_category', '=', 'room_category.id_category')
            ->select('bcl_pricelist.*', 'room_category.category_name as category_name', 'room_category.id_category as category_id')
            ->get();

        return view('bcl.pricelist.pricelist')
            ->with('pricelist', $pricelist)
            ->with('categories', room_category::all())
            ->with('pl_tambahan', extra_pricelist::all());
    }

    public function get_room_pricelist(Request $request)
    {
        return response()->json(Pricelist::where('room_category', $request->id)->orderBy('price')->get());
    }

    public function edit(Request $request)
    {
        return response()->json(Pricelist::find($request->id));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Pricelist(), 'Data Harga berhasil ditambahkan!');
    }

    public function update(Request $request)
    {
        $pl = Pricelist::find($request->id_pricelist);
        if (!$pl) {
            return redirect()->route('bcl.pricelist.index')->with('error', 'Data tidak ditemukan!');
        }

        return $this->save($request, $pl, 'Data Harga berhasil diubah!');
    }

    public function destroy(Request $request)
    {
        $pl = Pricelist::find($request->id);
        if (!$pl) {
            return redirect()->route('bcl.pricelist.index')->with('error', 'Data tidak ditemukan!');
        }
        $pl->delete();

        return redirect()->route('bcl.pricelist.index')->with('success', 'Data Harga berhasil dihapus!');
    }

    protected function save(Request $request, Pricelist $pl, string $message)
    {
        $request->merge([
            'harga' => RentalService::amount($request->harga),
            'jangka_waktu' => (int) RentalService::amount($request->jangka_waktu),
            'bonus_waktu' => (int) RentalService::amount($request->bonus_waktu),
        ]);

        try {
            $this->validate($request, [
                'tipe_kamar' => 'required|exists:bcl_room_category,id_category',
                'harga' => 'required|numeric|min:0',
                'jangka_waktu' => 'required|integer|min:1',
                'jangka_sewa' => 'required|in:Hari,Minggu,Bulan,Tahun',
                'bonus_waktu' => 'nullable|integer|min:0',
                'bonus_sewa' => 'nullable|in:Hari,Minggu,Bulan,Tahun',
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('bcl.pricelist.index')->with('error', collect($e->errors())->flatten()->first());
        }

        $pl->fill([
            'price' => $request->harga,
            'jangka_waktu' => $request->jangka_waktu,
            'jangka_sewa' => $request->jangka_sewa,
            'bonus_waktu' => $request->bonus_waktu ?: 0,
            'bonus_sewa' => $request->bonus_sewa,
            'room_category' => $request->tipe_kamar,
        ])->save();

        return redirect()->route('bcl.pricelist.index')->with('success', $message);
    }
}
