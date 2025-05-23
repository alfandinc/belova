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
use App\Models\ERM\Pasien;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\ResepFarmasi;
use Illuminate\Support\Str;



class EresepController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            // $user = Auth::user();
            // if ($user->hasRole('Perawat')) {
            //     $visitations->where('progress', 1);
            // } elseif ($user->hasRole('Dokter')) {
            //     $visitations->whereIn('progress', [2, 3]);
            // }

            return datatables()->of($visitations)
                ->addColumn('antrian', fn($v) => $v->no_antrian) // ✅ antrian dari database
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-')
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal', fn($v) => $v->tanggal_visitation)
                ->addColumn('status_dokumen', fn($v) => ucfirst($v->status_dokumen))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('progress', fn($v) => $v->progress) // 🛠️ Tambah kolom progress!
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $asesmenUrl = $user->hasRole('Perawat') ? route('erm.eresepfarmasi.create', $v->id)
                        : ($user->hasRole('Dokter') ? route('erm.eresepfarmasi.create', $v->id) : '#');
                    return '<a href="' . $asesmenUrl . '" class="btn btn-sm btn-primary">Lihat</a> ';
                })
                ->rawColumns(['dokumen'])
                ->make(true);
        }

        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        return view('erm.eresep.index', compact('dokters', 'metodeBayar'));
    }

    // ERESEP DOKTER

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
        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

        ResepDokter::create([
            'id' => $customId,
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
            do {
                $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
            } while (ResepDokter::where('id', $customId)->exists());

            ResepDokter::create([
                'id' => $customId,
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

    public function updateNonRacikan(Request $request, $id)
    {
        $data = $request->validate([
            'jumlah'       => 'required|integer|min:1',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        $resep = ResepDokter::findOrFail($id);
        $resep->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diubah',
            'data'    => $resep,
        ]);
    }

    // ERESEP FARMASI

    public function farmasicreate($visitationId)
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
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)->with('obat')->get();


        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;


        return view('erm.eresep.farmasi.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
        ], $pasienData, $createKunjunganData));
    }
    public function copyFromDokter($visitationId)
    {
        if (ResepFarmasi::where('visitation_id', $visitationId)->exists()) {
            return response()->json(['status' => 'info', 'message' => 'Resep sudah pernah disalin ke Farmasi.']);
        }

        $reseps = ResepDokter::where('visitation_id', $visitationId)->get();

        foreach ($reseps as $resep) {
            ResepFarmasi::create([
                'visitation_id' => $resep->visitation_id,
                'obat_id'        => $resep->obat_id,
                'jumlah'         => $resep->jumlah,
                'aturan_pakai'   => $resep->aturan_pakai,
                'racikan_ke'     => $resep->racikan_ke,
                'wadah'          => $resep->wadah,
                'bungkus'        => $resep->bungkus,
                'dosis'          => $resep->dosis,
                'dokter_id'      => optional($resep->visitation)->dokter_id,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Berhasil menyalin resep ke Farmasi.']);
    }
    public function getFarmasiResepJson($visitationId)
    {
        $reseps = ResepFarmasi::with('obat')
            ->where('visitation_id', $visitationId)
            ->get();

        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke')->values();
        $nonRacikans = $reseps->whereNull('racikan_ke')->values();

        return response()->json([
            'non_racikans' => $nonRacikans,
            'racikans' => $racikans,
        ]);
    }

    public function farmasistoreNonRacikan(Request $request)
    {

        $validated = $request->validate([
            'visitation_id' => 'required',
            'obat_id' => 'required',
            'jumlah' => 'required',
            'diskon' => 'required',
            'aturan_pakai' => 'required',
        ]);
        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

        $resep = ResepFarmasi::create([
            'id' => $customId,
            'tanggal_input' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'diskon' => $validated['diskon'],
            'aturan_pakai' => $validated['aturan_pakai'],
        ]);

        // Load relasi obat agar bisa diakses dari JS
        $resep->load('obat');

        return response()->json([
            'success' => true,
            'message' => 'Obat non-racikan berhasil disimpan.',
            'data' => $resep
        ]);
    }

    public function farmasistoreRacikan(Request $request)
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
            do {
                $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
            } while (ResepDokter::where('id', $customId)->exists());

            ResepDokter::create([
                'id' => $customId,
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

    public function farmasidestroyNonRacikan($id)
    {
        $resep = ResepFarmasi::findOrFail($id);
        $resep->delete();

        return response()->json(['message' => 'Resep berhasil dihapus']);
    }

    public function farmasidestroyRacikan($racikanKe, Request $request)
    {
        $visitationId = $request->visitation_id;

        // Temukan racikan berdasarkan visitation_id dan racikan_ke
        $racikan = ResepFarmasi::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->first();

        if ($racikan) {
            $racikan->delete();
            return response()->json(['message' => 'Racikan berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Racikan tidak ditemukan'], 404);
        }
    }

    public function farmasiupdateNonRacikan(Request $request, $id)
    {
        $data = $request->validate([
            'jumlah'       => 'required|integer|min:1',
            'diskon'       => 'integer|min:1|max:100',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        $resep = ResepFarmasi::findOrFail($id);
        $resep->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diubah',
            'data'    => $resep,
        ]);
    }

    public function getRiwayatDokter($pasienId)
    {
        $reseps = ResepDokter::with(['obat', 'visitation'])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderBy('visitation_id')
            ->get()
            ->groupBy('visitation_id');

        return view('erm.partials.resep-riwayatdokter', compact('reseps'));
    }

    public function getRiwayatFarmasi($pasienId)
    {
        $reseps = ResepFarmasi::with(['obat', 'visitation'])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderBy('visitation_id')
            ->get()
            ->groupBy('visitation_id');

        return view('erm.partials.resep-riwayatfarmasi', compact('reseps'));
    }
}
