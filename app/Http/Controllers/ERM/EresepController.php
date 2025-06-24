<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Obat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ERM\ResepDokter;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\EdukasiObat;
use App\Models\ERM\JasaFarmasi;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\WadahObat;
use App\Models\User;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\ResepDetail;



class EresepController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar', 'dokter.user', 'dokter.spesialisasi'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }
            if ($request->dokter_id) {
                $visitations->where('dokter_id', $request->dokter_id);
            }
            if ($request->klinik_id) {
                $visitations->where('klinik_id', $request->klinik_id);
            }

            $visitations->whereIn('jenis_kunjungan', [1, 2]);

            $user = Auth::user();
            if ($user->hasRole('Farmasi')) {
                $visitations->where('status_kunjungan', 2);
            }

            if ($request->status_resep !== null && $request->status_resep !== '') {
                // Filter visitations that have a resepdetail with the selected status
                $visitations->whereExists(function($query) use ($request) {
                    $query->select(DB::raw(1))
                        ->from('erm_resepdetail')
                        ->whereColumn('erm_resepdetail.visitation_id', 'erm_visitations.id')
                        ->where('erm_resepdetail.status', $request->status_resep);
                });
            }

            return datatables()->of($visitations)
                ->addColumn('antrian', fn($v) => $v->no_antrian) // ✅ antrian dari database
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-')
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal_visitation', function($v) {
                    if (!$v->tanggal_visitation) return '-';
                    \Carbon\Carbon::setLocale('id');
                    return \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                })
                ->addColumn('status_dokumen', fn($v) => ucfirst($v->status_dokumen))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('status_kunjungan', fn($v) => $v->progress) // 🛠️ Tambah kolom progress!
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $asesmenUrl = $user->hasRole('Farmasi') ? route('erm.eresepfarmasi.create', $v->id)
                        : ($user->hasRole('Farmasi') ? route('erm.eresepfarmasi.create', $v->id) : '#');
                    return '<a href="' . $asesmenUrl . '" class="btn btn-sm btn-primary">Lihat</a> ';
                })
                ->addColumn('nama_dokter', function($v) {
                    return $v->dokter && $v->dokter->user ? $v->dokter->user->name : '-';
                })
                ->addColumn('spesialisasi', function($v) {
                    return $v->dokter && $v->dokter->spesialisasi ? $v->dokter->spesialisasi->nama : '-';
                })
                ->addColumn('no_resep', function($v) {
                    // Ambil no_resep dari erm_resepdetail berdasarkan visitation_id
                    return \App\Models\ERM\ResepDetail::where('visitation_id', $v->id)->value('no_resep') ?? '-';
                })
                ->addColumn('asesmen_selesai', function($v) {
                    // Cari asesmen penunjang
                    $asesmenPenunjang = \DB::table('erm_asesmen_penunjang')
                        ->where('visitation_id', $v->id)
                        ->first();
                    if ($asesmenPenunjang && $asesmenPenunjang->created_at) {
                        \Carbon\Carbon::setLocale('id');
                        return \Carbon\Carbon::parse($asesmenPenunjang->created_at)->translatedFormat('H:i');
                    }
                    // Jika tidak ada, cari dari cppt
                    $cppt = \DB::table('erm_cppt')
                        ->where('visitation_id', $v->id)
                        ->orderBy('created_at', 'asc')
                        ->first();
                    if ($cppt && $cppt->created_at) {
                        \Carbon\Carbon::setLocale('id');
                        return \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('H:i');
                    }
                    return '-';
                })
                ->filterColumn('nama_pasien', function($query, $keyword) {
                    $query->whereHas('pasien', function($q) use ($keyword) {
                        $q->where('nama', 'like', "%$keyword%");
                    });
                })
                ->filterColumn('no_resep', function($query, $keyword) {
                    $query->whereExists(function($q) use ($keyword) {
                        $q->select(DB::raw(1))
                          ->from('erm_resepdetail')
                          ->whereColumn('erm_resepdetail.visitation_id', 'erm_visitations.id')
                          ->where('erm_resepdetail.no_resep', 'like', "%$keyword%");
                    });
                })
                ->rawColumns(['dokumen'])
                ->make(true);
        }

        $kliniks = \App\Models\ERM\Klinik::all();
        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        return view('erm.eresep.index', compact('dokters', 'metodeBayar', 'kliniks'));
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
        $reseps = ResepDokter::where('visitation_id', $visitationId)->with('obat', 'wadah')->get();

        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Get medications that exist in both doctor and pharmacy prescriptions
        $farmasiObatIds = ResepFarmasi::where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->pluck('obat_id')
            ->toArray();

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;

        $wadah = WadahObat::all();

        // dd($racikans);
 $catatan_resep = ResepDetail::where('visitation_id', $visitationId)->value('catatan_dokter');

        return view('erm.eresep.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'wadah' => $wadah,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
            'catatan_resep' => $catatan_resep,
            'farmasiObatIds' => $farmasiObatIds,
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

        $resep = ResepDokter::create([
            'id' => $customId,
            'created_at' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'aturan_pakai' => $validated['aturan_pakai'],
        ]);

        $resep->load('obat'); // ✅ load the obat relation here

        return response()->json([
            'success' => true,
            'message' => 'Obat non-racikan berhasil disimpan.',
            'data' => $resep
        ]);
    }

    public function storeRacikan(Request $request)
    {
        $validated = $request->validate([
            'visitation_id' => 'required',
            'racikan_ke' => 'required|integer',
            'wadah' => 'required',
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
                'visitation_id' => $validated['visitation_id'],
                'obat_id' => $obat['obat_id'],
                // 'jumlah' => 1, // atau sesuai jumlah per item racikan jika berbeda
                'aturan_pakai' => $validated['aturan_pakai'],
                'racikan_ke' => $validated['racikan_ke'],
                'wadah_id' => $validated['wadah'],
                'bungkus' => $validated['bungkus'],
                'dosis' => $obat['dosis'],

                'created_at' => now(),
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

        // Delete ALL records with matching racikan_ke and visitation_id
        $deleted = ResepDokter::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->delete();

        if ($deleted) {
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

    public function updateRacikan(Request $request, $racikanKe)
    {
        try {
            $validated = $request->validate([
                'visitation_id' => 'required',
                'wadah' => 'required',
                'bungkus' => 'required|integer|min:1',
                'aturan_pakai' => 'required|string',
            ]);

            // Update all records with matching racikan_ke and visitation_id
            $updated = ResepDokter::where('racikan_ke', $racikanKe)
                ->where('visitation_id', $validated['visitation_id'])
                ->update([
                    'wadah_id' => $validated['wadah'],
                    'bungkus' => $validated['bungkus'],
                    'aturan_pakai' => $validated['aturan_pakai'],
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Racikan berhasil diupdate'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Racikan tidak ditemukan'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate racikan: ' . $e->getMessage()
            ], 500);
        }
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

        $wadah = WadahObat::all();

        // Ambil catatan resep dari erm_resepdetail
        $catatan_resep = ResepDetail::where('visitation_id', $visitationId)->value('catatan_dokter');

        return view('erm.eresep.farmasi.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'wadah' => $wadah,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'lastRacikanKe' => $lastRacikanKe,
            'catatan_resep' => $catatan_resep,
        ], $pasienData, $createKunjunganData));
    }
    public function copyFromDokter($visitationId)
    {
        if (ResepFarmasi::where('visitation_id', $visitationId)->exists()) {
            return response()->json(['status' => 'info', 'message' => 'Resep sudah pernah disalin ke Farmasi.']);
        }

        $reseps = ResepDokter::where('visitation_id', $visitationId)->get();

        foreach ($reseps as $resep) {
            // Retrieve the harga of the obat
            $obat = Obat::find($resep->obat_id);
            $harga = $obat ? $obat->harga_nonfornas : null;
            // Generate a unique custom ID
            do {
                $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
            } while (ResepFarmasi::where('id', $customId)->exists());

            ResepFarmasi::create([
                'id'             => $customId, // Store the custom ID here
                'visitation_id'  => $resep->visitation_id,
                'obat_id'        => $resep->obat_id,
                'jumlah'         => $resep->jumlah,
                'aturan_pakai'   => $resep->aturan_pakai,
                'racikan_ke'     => $resep->racikan_ke,
                'wadah_id'       => $resep->wadah_id, // FIXED: use wadah_id, not wadah
                'bungkus'        => $resep->bungkus,
                'dosis'          => $resep->dosis,
                'dokter_id'      => optional($resep->visitation)->dokter_id,
                'harga'          => $harga,
                // 'total'          => $resep->jumlah * $harga,
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
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
        ]);
    }

    public function farmasistoreNonRacikan(Request $request)
    {

        $validated = $request->validate([
            'visitation_id' => 'required',
            'obat_id' => 'required',
            'jumlah' => 'required',
            'aturan_pakai' => 'required',

            'diskon' => 'required',
            'harga' => 'required',
        ]);
        $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

        $resep = ResepFarmasi::create([
            'id' => $customId,
            'created_at' => Carbon::now(),
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $validated['obat_id'],
            'jumlah' => $validated['jumlah'],
            'aturan_pakai' => $validated['aturan_pakai'],

            'diskon' => $validated['diskon'],
            'harga' => $validated['harga'],

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
        'wadah' => 'required',
        'bungkus' => 'required|integer',
        'aturan_pakai' => 'required|string',
        'obats' => 'required|array|min:1',
        'obats.*.obat_id' => 'required',
        'obats.*.dosis' => 'required|string',
    ]);

    foreach ($validated['obats'] as $obat) {
        do {
            $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
        } while (ResepFarmasi::where('id', $customId)->exists());

        // Get the obat details to calculate proportional price
        $obatModel = Obat::findOrFail($obat['obat_id']);
        $basePrice = $obatModel->harga_nonfornas ?? 0;
        
        // Extract numeric values from dosis strings
        $prescribedDosisStr = $obat['dosis'];
        $baseDosisStr = $obatModel->dosis ?? '';
        
        // Extract numeric part using regex
        preg_match('/(\d+(\.\d+)?)/', $prescribedDosisStr, $prescribedMatches);
        preg_match('/(\d+(\.\d+)?)/', $baseDosisStr, $baseMatches);
        
        $prescribedDosis = !empty($prescribedMatches[1]) ? (float)$prescribedMatches[1] : 0;
        $baseDosis = !empty($baseMatches[1]) ? (float)$baseMatches[1] : 0;
        
        // Calculate proportional price
        $harga = $basePrice;
        if ($baseDosis > 0 && $prescribedDosis > 0) {
            $dosisRatio = $prescribedDosis / $baseDosis;
            $harga = $basePrice * $dosisRatio;
            
        }

        ResepFarmasi::create([
            'id' => $customId,
            'visitation_id' => $validated['visitation_id'],
            'obat_id' => $obat['obat_id'],
            'aturan_pakai' => $validated['aturan_pakai'],
            'racikan_ke' => $validated['racikan_ke'],
            'wadah_id' => $validated['wadah'],
            'bungkus' => $validated['bungkus'],
            'dosis' => $obat['dosis'],
            'harga' => $harga, // Store the calculated proportional price
            'created_at' => now(),
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

        // Delete ALL records with matching racikan_ke and visitation_id
        $deleted = ResepFarmasi::where('racikan_ke', $racikanKe)
            ->where('visitation_id', $visitationId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Racikan berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Racikan tidak ditemukan'], 404);
        }
    }

    public function farmasiupdateNonRacikan(Request $request, $id)
    {
        $data = $request->validate([
            'jumlah'       => 'required|integer|min:1',
            'diskon'       => 'integer|max:100',
            'aturan_pakai' => 'required|string|max:255',
        ]);

        $resep = ResepFarmasi::findOrFail($id);

        // Retrieve the existing total value
        $existingTotal = $resep->harga;

        // Calculate the new total after applying the discount
        $diskon = $data['diskon'] ?? 0; // Default diskon to 0 if not provided
        $newTotal = $existingTotal * (1 - $diskon / 100);

        // Update the prescription with the calculated total and other fields
        $resep->update(array_merge($data, ['harga' => $newTotal]));

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diubah',
            'data'    => $resep,
        ]);
    }

    public function farmasiupdateRacikan(Request $request, $racikanKe)
    {
        try {
            $validated = $request->validate([
                'visitation_id' => 'required',
                'bungkus' => 'required|integer',
                'aturan_pakai' => 'required|string',
            ]);

            $updated = \App\Models\ERM\ResepFarmasi::where('visitation_id', $validated['visitation_id'])
                ->where('racikan_ke', $racikanKe)
                ->update([
                    'bungkus' => $validated['bungkus'],
                    'aturan_pakai' => $validated['aturan_pakai'],
                ]);

            if ($updated) {
                return response()->json(['success' => true, 'message' => 'Racikan berhasil diupdate.']);
            } else {
                // Log the query information for debugging
                \Illuminate\Support\Facades\Log::info('Racikan update failed', [
                    'visitation_id' => $validated['visitation_id'],
                    'racikan_ke' => $racikanKe,
                    'bungkus' => $validated['bungkus'],
                    'aturan_pakai' => $validated['aturan_pakai']
                ]);
                return response()->json(['success' => false, 'message' => 'Tidak ada data yang diupdate.'], 404);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating racikan: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // SUBMIT RESEP

    public function submitResep(Request $request)
{
    $visitationId = $request->input('visitation_id');
    $force = $request->input('force', false);

    // Check if resep already submitted
    $resepDetail = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->first();
    if ($resepDetail && $resepDetail->status == 1 && !$force) {
        return response()->json([
            'status' => 'warning',
            'message' => 'Resep sudah pernah disubmit. Lanjutkan dan timpa billing lama?',
            'need_confirm' => true
        ], 200);
    }

    // If force, delete all billing for this visitation
    if ($force) {
        Billing::where('visitation_id', $visitationId)->delete();
    }

    // Fetch all related prescriptions
    $reseps = ResepFarmasi::where('visitation_id', $visitationId)->with('obat')->get();
    // Update resepdetail status to 1
    \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->update(['status' => 1]);

    // Bill for each medication
    foreach ($reseps as $resep) {
        Billing::updateOrCreate(
            [
                'billable_id' => $resep->id,
                'billable_type' => ResepFarmasi::class,
            ],
            [
                'visitation_id' => $resep->visitation_id,
                'jumlah' => $resep->harga ?? 0,
                'keterangan' => 'Obat: ' . ($resep->obat->nama ?? 'Tanpa Nama') . 
                                ($resep->racikan_ke ? ' (Racikan #' . $resep->racikan_ke . ')' : ''),
            ]
        );
    }
    


    return response()->json([
        'status' => 'success',
        'message' => 'Resep berhasil disubmit!'
    ]);
}

    // RIWAYAT DOKTER & FARMASI

    public function getRiwayatDokter($pasienId)
    {
        $reseps = ResepDokter::with(['obat', 'visitation'])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderByDesc(
                \App\Models\ERM\Visitation::select('tanggal_visitation')
                    ->whereColumn('erm_visitations.id', 'erm_resepdokter.visitation_id')
                    ->limit(1)
            )
            ->get()
            ->groupBy('visitation_id');

        return view('erm.partials.resep-riwayatdokter', compact('reseps'));
    }

    public function getRiwayatFarmasi($pasienId)
    {
        $reseps = ResepFarmasi::with(['obat', 'visitation'])
            ->whereHas('visitation', fn($q) => $q->where('pasien_id', $pasienId))
            ->orderByDesc(
                \App\Models\ERM\Visitation::select('tanggal_visitation')
                    ->whereColumn('erm_visitations.id', 'erm_resepfarmasi.visitation_id')
                    ->limit(1)
            )
            ->get()
            ->groupBy('visitation_id');

        return view('erm.partials.resep-riwayatfarmasi', compact('reseps'));
    }

    //Wadah Obat
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $wadahs = WadahObat::where('nama', 'like', '%' . $query . '%')->get();

        return response()->json($wadahs->map(function ($wadah) {
            return [
                'id' => $wadah->id,
                'text' => $wadah->nama . ' ' . $wadah->harga,
            ];
        }));
    }


    public function getResepDokterByVisitation($visitationId)
    {
        $nonRacikans = ResepDokter::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->get();

        $racikans = ResepDokter::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNotNull('racikan_ke')
            ->get()
            ->groupBy('racikan_ke');

        return response()->json([
            'success' => true,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans
        ]);
    }

    public function getResepFarmasiByVisitation($visitationId)
    {
        $nonRacikans = ResepFarmasi::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNull('racikan_ke')
            ->get();

        $racikans = ResepFarmasi::with(['obat', 'wadah'])
            ->where('visitation_id', $visitationId)
            ->whereNotNull('racikan_ke')
            ->get()
            ->groupBy('racikan_ke');

        return response()->json([
            'success' => true,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans
        ]);
    }

    public function printResep($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])->findOrFail($visitationId);

        // Get prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat', 'wadah'])
            ->get();

        // Separate racikan and non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Get patient allergies
        $alergis = DB::table('erm_alergi')
            ->join('erm_zataktif', 'erm_alergi.zataktif_id', '=', 'erm_zataktif.id')
            ->where('erm_alergi.pasien_id', $visitation->pasien_id)
            ->select('erm_zataktif.nama as zataktif_nama', 'erm_alergi.katakunci')
            ->get();

        // Get asesmen penunjang data for diagnoses and follow-up
        $asesmenPenunjang = DB::table('erm_asesmen_penunjang')
            ->where('visitation_id', $visitationId)
            ->first();

        // Get no_resep from ResepDetail
        $noResep = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->value('no_resep');

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.print', [
            'visitation' => $visitation,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
            'asesmenPenunjang' => $asesmenPenunjang,
            'noResep' => $noResep,
        ]);

        // Set PDF options for A4 landscape
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        // Return the PDF for download or inline display
        return $pdf->stream('resep-' . $visitation->id . '.pdf');
    }

    public function getApotekers()
    {
        $apotekers = User::role('Farmasi')->get(['id', 'name']);
        return response()->json($apotekers);
    }

    public function storeEdukasiObat(Request $request)
    {
        $validated = $request->validate([
            'visitation_id' => 'required',
            'simpan_etiket_label' => 'boolean',
            'simpan_suhu_kulkas' => 'boolean',
            'simpan_tempat_kering' => 'boolean',
            'hindarkan_jangkauan_anak' => 'boolean',
            'insulin_brosur' => 'nullable|string',
            'inhalasi_brosur' => 'nullable|string',
            'apoteker_id' => 'required',
            // 'total_pembayaran' => 'nullable|numeric',
        ]);

        // Create or update edukasi
        $edukasi = EdukasiObat::updateOrCreate(
            ['visitation_id' => $validated['visitation_id']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Edukasi obat berhasil disimpan',
            'data' => $edukasi
        ]);
    }

    public function printEdukasiObat($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])
            ->findOrFail($visitationId);

        // Get edukasi data
        $edukasi = EdukasiObat::with('apoteker')
            ->where('visitation_id', $visitationId)
            ->first();

        if (!$edukasi) {
            return redirect()->back()->with('error', 'Data edukasi obat tidak ditemukan');
        }

        // Get prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat'])
            ->get();

        // Separate racikan and non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Get patient allergies
        $alergis = DB::table('erm_alergi')
            ->join('erm_zataktif', 'erm_alergi.zataktif_id', '=', 'erm_zataktif.id')
            ->where('erm_alergi.pasien_id', $visitation->pasien_id)
            ->select('erm_zataktif.nama as zataktif_nama')
            ->get();

        // Get no_resep from ResepDetail
        $noResep = \App\Models\ERM\ResepDetail::where('visitation_id', $visitationId)->value('no_resep');

        // Generate QR code data for apoteker and pasien
        $apotekerQr = 'APOTEKER|' .
            ($edukasi->apoteker->id ?? '-') . '|' .
            ($edukasi->apoteker->name ?? '-') . '|' .
            now()->format('Y-m-d H:i:s');

        $pasienQr = 'PASIEN|' .
            ($visitation->pasien->id ?? '-') . '|' .
            ($visitation->pasien->nama ?? '-') . '|' .
            now()->format('Y-m-d H:i:s');

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.edukasi-print', [
            'visitation' => $visitation,
            'edukasi' => $edukasi,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
            'noResep' => $noResep,
            'apotekerQr' => $apotekerQr,
            'pasienQr' => $pasienQr,
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');

        // Return the PDF for download or inline display
        return $pdf->stream('edukasi-obat-' . $visitationId . '.pdf');
    }

    public function printEtiket($visitationId)
    {
        // Get the visitation data with all necessary relations
        $visitation = Visitation::with(['pasien', 'dokter.user', 'metodeBayar', 'klinik'])->findOrFail($visitationId);

        // Get all prescription items
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)
            ->with(['obat', 'wadah'])
            ->get();

        $nonRacikans = $reseps->whereNull('racikan_ke');
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        if ($reseps->isEmpty()) {
            return back()->with('error', 'Tidak ada obat untuk dicetak etiket.');
        }

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.etiket-print', [
            'visitation' => $visitation,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
        ]);

        // Set PDF options for 78x60mm (7.8cm x 6cm) landscape format
        // Convert to points: 1cm = 28.35 points
        $height = 78 * 2.835; // 221.13 points
        $width= 60 * 2.835; // 170.1 points
        
        $pdf->setPaper([0, 0, $width, $height], 'landscape');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);

        // Return the PDF for download or inline display
        return $pdf->stream('etiket-' . $visitation->id . '.pdf');
    }

    public function copyFromHistory(Request $request)
    {
        $validated = $request->validate([
            'source_visitation_id' => 'required',
            'target_visitation_id' => 'required',
            'source_type' => 'required|in:dokter,farmasi',
        ]);

        $targetVisitationId = $validated['target_visitation_id'];
        $sourceVisitationId = $validated['source_visitation_id'];
        $sourceType = $validated['source_type'];

        // Check if prescription already exists for target visitation
        if (ResepDokter::where('visitation_id', $targetVisitationId)->exists()) {
            return response()->json([
                'status' => 'info',
                'message' => 'Resep sudah ada untuk kunjungan ini. Harap hapus resep yang ada sebelum menyalin yang baru.'
            ]);
        }

        // Fetch source prescriptions based on source type
        $sourceReseps = $sourceType === 'dokter'
            ? ResepDokter::where('visitation_id', $sourceVisitationId)->get()
            : ResepFarmasi::where('visitation_id', $sourceVisitationId)->get();

        if ($sourceReseps->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada resep yang ditemukan untuk disalin.'
            ]);
        }

        // Copy each prescription item
        foreach ($sourceReseps as $resep) {
            // Generate unique ID
            $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

            // Get obat information if needed
            $obat = Obat::find($resep->obat_id);
            $harga = $obat ? $obat->harga_nonfornas : 0;

            // Create new ResepFarmasi record
            ResepDokter::create([
                'id' => $customId,
                'visitation_id' => $targetVisitationId,
                'obat_id' => $resep->obat_id,
                'jumlah' => $resep->jumlah,
                'dosis' => $resep->dosis,
                'bungkus' => $resep->bungkus,
                'racikan_ke' => $resep->racikan_ke,
                'aturan_pakai' => $resep->aturan_pakai,
                'wadah_id' => $resep->wadah_id,
                'harga' => $harga,
                'diskon' => 0, // Default value
                'total' => $harga * ($resep->jumlah ?? 1),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resep berhasil disalin ke farmasi.',
            'redirect' => route('erm.eresepfarmasi.create', ['visitation_id' => $targetVisitationId])
        ]);
    }
    public function copyFromHistoryFarmasi(Request $request)
    {
        $validated = $request->validate([
            'source_visitation_id' => 'required',
            'target_visitation_id' => 'required',
            'source_type' => 'required|in:dokter,farmasi',
        ]);

        $targetVisitationId = $validated['target_visitation_id'];
        $sourceVisitationId = $validated['source_visitation_id'];
        $sourceType = $validated['source_type'];

        // Check if prescription already exists for target visitation
        if (ResepFarmasi::where('visitation_id', $targetVisitationId)->exists()) {
            return response()->json([
                'status' => 'info',
                'message' => 'Resep sudah ada untuk kunjungan ini. Harap hapus resep yang ada sebelum menyalin yang baru.'
            ]);
        }

        // Fetch source prescriptions based on source type
        $sourceReseps = $sourceType === 'dokter'
            ? ResepDokter::where('visitation_id', $sourceVisitationId)->get()
            : ResepFarmasi::where('visitation_id', $sourceVisitationId)->get();

        if ($sourceReseps->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada resep yang ditemukan untuk disalin.'
            ]);
        }

        // Copy each prescription item
        foreach ($sourceReseps as $resep) {
            // Generate unique ID
            $customId = now()->format('YmdHis') . strtoupper(Str::random(7));

            // Get obat information if needed
            $obat = Obat::find($resep->obat_id);
            $harga = $obat ? $obat->harga_nonfornas : 0;

            // Create new ResepFarmasi record
            ResepFarmasi::create([
                'id' => $customId,
                'visitation_id' => $targetVisitationId,
                'obat_id' => $resep->obat_id,
                'jumlah' => $resep->jumlah,
                'dosis' => $resep->dosis,
                'bungkus' => $resep->bungkus,
                'racikan_ke' => $resep->racikan_ke,
                'aturan_pakai' => $resep->aturan_pakai,
                'wadah_id' => $resep->wadah_id,
                'harga' => $harga,
                'diskon' => 0, // Default value
                'total' => $harga * ($resep->jumlah ?? 1),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resep berhasil disalin ke farmasi.',
            'redirect' => route('erm.eresepfarmasi.create', ['visitation_id' => $targetVisitationId])
        ]);
    }
    
}
