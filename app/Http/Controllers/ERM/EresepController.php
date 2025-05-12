<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Obat;
use App\Models\ERM\ZatAktif;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ERM\Alergi;
use App\Models\ERM\ResepDokter;
use Illuminate\Support\Facades\Auth;

class EresepController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        // Ambil pasien id
        $pasienId = $visitation->pasien_id;

        // Ambil zat aktif yang pasien alergi
        $zatAlergi = DB::table('erm_alergi')
            ->where('pasien_id', $pasienId)
            ->pluck('zataktif_id')
            ->toArray();

        // Ambil obat yang tidak punya zat aktif dari alergi
        $obats = Obat::whereDoesntHave('zatAktifs', function ($query) use ($zatAlergi) {
            $query->whereIn('erm_zataktif.id', $zatAlergi);
        })->get();

        // $obats = Obat::all();

        // Ambil semua resep berdasarkan visitation_id
        $reseps = ResepDokter::where('visitation_id', $visitationId)->with('obat')->get();

        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;


        return view('erm.eresep.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
        ], $pasienData, $createKunjunganData));
    }

    public function storeNonRacikan(Request $request)
    {

        $validated = $request->validate([
            'visitation_id' => 'required',
            'obat_id' => 'required',
            'jumlah' => 'required',
            'aturan_pakai' => 'required',
        ]);

        ResepDokter::create([
            'tanggal_input' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'aturan_pakai' => $validated['aturan_pakai'],
        ]);

        return response()->json(['success' => true, 'message' => 'Obat non-racikan berhasil disimpan.']);
    }

    public function storeRacikan(Request $request)
    {
        $validated = $request->validate([
            'visitation_id' => 'required',
            'racikan_ke' => 'required|integer',
            'wadah' => 'required|string',
            'bungkus' => 'required|integer',
            'aturan_pakai' => 'required|string',
            'obats' => 'required|array|min:1',
            'obats.*.obat_id' => 'required',
            'obats.*.dosis' => 'required|string',
        ]);

        foreach ($validated['obats'] as $obat) {
            ResepDokter::create([
                'tanggal_input' => now(),
                'visitation_id' => $validated['visitation_id'],
                'obat_id' => $obat['obat_id'],
                'jumlah' => 1, // atau sesuai jumlah per item racikan jika berbeda
                'aturan_pakai' => $validated['aturan_pakai'],
                'racikan_ke' => $validated['racikan_ke'],
                'wadah' => $validated['wadah'],
                'bungkus' => $validated['bungkus'],
                'dosis' => $obat['dosis'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Racikan berhasil disimpan.']);
    }

    public function destroyNonRacikan($id)
    {
        $resep = ResepDokter::findOrFail($id);
        $resep->delete();

        return response()->json(['message' => 'Resep berhasil dihapus']);
    }

    public function destroyRacikan($racikanKe, Request $request)
    {
        $visitationId = $request->visitation_id;

        // Temukan racikan berdasarkan visitation_id dan racikan_ke
        $racikan = ResepDokter::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->first();

        if ($racikan) {
            $racikan->delete();
            return response()->json(['message' => 'Racikan berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Racikan tidak ditemukan'], 404);
        }
    }
}
