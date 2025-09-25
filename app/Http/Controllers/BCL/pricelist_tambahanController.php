<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\extra_pricelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class pricelist_tambahanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            DB::beginTransaction();
            // sanitize numeric formatted inputs (e.g. '1.000,00' or '1,000.00')
            if ($request->has('harga')) {
                $raw = $request->input('harga');
                // remove spaces
                $raw = trim($raw);
                // normalize comma decimal (e.g. '1.234,56' => '1234.56')
                if (preg_match('/,\d{1,2}$/', $raw)) {
                    $raw = str_replace('.', '', $raw);
                    $raw = str_replace(',', '.', $raw);
                } else {
                    // remove thousand separators
                    $raw = str_replace(',', '', $raw);
                }
                $request->merge(['harga' => $raw]);
            }
            if ($request->has('jangka_waktu')) {
                $raw = $request->input('jangka_waktu');
                $raw = trim($raw);
                $raw = preg_replace('/[^0-9]/', '', $raw);
                $request->merge(['jangka_waktu' => $raw]);
            }
            $this->validate($request, [
                // table is bcl_extra_pricelist (model uses protected $table = 'bcl_extra_pricelist')
                'nama'     => 'required|unique:bcl_extra_pricelist,nama',
                'harga'     => 'required|numeric',
                'jangka_waktu' => 'required|numeric',
                'jangka_sewa' => 'required',
            ]);
            $store = extra_pricelist::create([
                'nama'     => $request->nama,
                'qty'   => $request->jangka_waktu,
                'harga'     => $request->harga,
                'jangka_sewa'   => $request->jangka_sewa,
            ]);
            DB::commit();
            return back()->withSuccess('Berhasil menambahkan harga tambahan!');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(extra_pricelist $extra_pricelist)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(extra_pricelist $extra_pricelist, Request $request)
    {
        $data = extra_pricelist::findorfail($request->id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, extra_pricelist $extra_pricelist)
    {
        try {
            // sanitize numeric formatted inputs
            if ($request->has('harga')) {
                $raw = $request->input('harga');
                $raw = trim($raw);
                if (preg_match('/,\d{1,2}$/', $raw)) {
                    $raw = str_replace('.', '', $raw);
                    $raw = str_replace(',', '.', $raw);
                } else {
                    $raw = str_replace(',', '', $raw);
                }
                $request->merge(['harga' => $raw]);
            }
            if ($request->has('jangka_waktu')) {
                $raw = $request->input('jangka_waktu');
                $raw = trim($raw);
                $raw = preg_replace('/[^0-9]/', '', $raw);
                $request->merge(['jangka_waktu' => $raw]);
            }
            $this->validate($request, [
                'id' => 'required',
                'nama' => 'required',
                'jangka_waktu' => 'required|numeric',
                'jangka_sewa' => 'required',
                'harga' => 'required|numeric',
            ]);
            $pl = extra_pricelist::findorfail($request->id);
            // DB column for duration quantity is 'qty' (migration defines integer 'qty')
            $pl->update([
                'nama' => $request->nama,
                'qty' => $request->jangka_waktu,
                'jangka_sewa' => $request->jangka_sewa,
                'harga' => $request->harga,
            ]);
            return back()->with(['success' => 'Data berhasil diubah']);
        } catch (\Exception $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(extra_pricelist $extra_pricelist, Request $request)
    {
        try {
            $data = extra_pricelist::findorfail($request->id);
            $data->delete();
            return back()->with(['success' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
