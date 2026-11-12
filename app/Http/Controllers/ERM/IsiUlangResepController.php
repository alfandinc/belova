<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Services\ERM\StokResepService;
use App\Support\Stok\MutasiAkses;
use Illuminate\Http\Request;

/**
 * Saran Isi Ulang Apotek: obat the resep gudang will be short of for the patients registered for the coming days
 * (their resep when it exists, else predicted from the patient's history; no stok minimum is typed in per obat),
 * with the quantity to transfer from another gudang.
 * The transfer itself is created through Transfer Antar Gudang (mutasi-gudang.store), so approval stays the same.
 */
class IsiUlangResepController extends Controller
{
    public function index(StokResepService $service)
    {
        return view('erm.isi-ulang-resep.index', [
            'gudangResep' => \App\Models\ERM\Gudang::where('id', $service->gudangResepId())->value('nama'),
            'gudangLain' => $service->gudangLain(),
            'hariKunjungan' => StokResepService::HARI_KUNJUNGAN,
            'riwayatKunjungan' => StokResepService::RIWAYAT_KUNJUNGAN,
            'canRequest' => MutasiAkses::canAny(MutasiAkses::MUTASI_GUDANG, 'request'),
        ]);
    }

    public function data(Request $request, StokResepService $service)
    {
        return response()->json($service->saranIsiUlang($service->normalizeSaranParams($request->all())));
    }
}
