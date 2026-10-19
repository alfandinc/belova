<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\extra_pricelist;
use App\Services\BCL\RentalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class pricelist_tambahanController extends Controller
{
    public function store(Request $request)
    {
        return $this->save($request, new extra_pricelist(), 'Berhasil menambahkan harga tambahan!');
    }

    public function edit(Request $request)
    {
        return response()->json(extra_pricelist::findOrFail($request->id));
    }

    public function update(Request $request)
    {
        $pl = extra_pricelist::find($request->id);
        if (!$pl) {
            return back()->with('error', 'Data tidak ditemukan');
        }

        return $this->save($request, $pl, 'Data berhasil diubah');
    }

    public function destroy(Request $request)
    {
        $pl = extra_pricelist::find($request->id);
        if (!$pl) {
            return back()->with('error', 'Data tidak ditemukan');
        }
        $pl->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }

    protected function save(Request $request, extra_pricelist $pl, string $message)
    {
        $request->merge([
            'harga' => RentalService::amount($request->harga),
            'jangka_waktu' => (int) RentalService::amount($request->jangka_waktu),
        ]);

        try {
            $this->validate($request, [
                'nama' => ['required', Rule::unique('bcl_extra_pricelist', 'nama')->ignore($pl->id)],
                'harga' => 'required|numeric|min:0',
                'jangka_waktu' => 'required|integer|min:1',
                'jangka_sewa' => 'required|in:Hari,Minggu,Bulan,Tahun',
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $pl->fill([
            'nama' => $request->nama,
            // The 'qty' column stores the duration per price unit.
            'qty' => $request->jangka_waktu,
            'harga' => $request->harga,
            'jangka_sewa' => $request->jangka_sewa,
        ])->save();

        return back()->with('success', $message);
    }
}
