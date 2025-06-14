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
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\WadahObat;
use App\Models\User;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;



class EresepController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            $visitations->whereIn('jenis_kunjungan', [1, 2]);

            $user = Auth::user();
            if ($user->hasRole('Farmasi')) {
                $visitations->where('status_kunjungan', 2);
            }

            return datatables()->of($visitations)
                ->addColumn('antrian', fn($v) => $v->no_antrian) // ✅ antrian dari database
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-')
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal_visitation', fn($v) => $v->tanggal_visitation)
                ->addColumn('status_dokumen', fn($v) => ucfirst($v->status_dokumen))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('status_kunjungan', fn($v) => $v->progress) // 🛠️ Tambah kolom progress!
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $asesmenUrl = $user->hasRole('Farmasi') ? route('erm.eresepfarmasi.create', $v->id)
                        : ($user->hasRole('Farmasi') ? route('erm.eresepfarmasi.create', $v->id) : '#');
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
        $reseps = ResepDokter::where('visitation_id', $visitationId)->with('obat', 'wadah')->get();

        // Kelompokkan racikan berdasarkan racikan_ke
        $racikans = $reseps->whereNotNull('racikan_ke')->groupBy('racikan_ke');

        // Ambil non-racikan
        $nonRacikans = $reseps->whereNull('racikan_ke');

        // Hitung nilai racikan_ke terakhir dari database
        $lastRacikanKe = $reseps->whereNotNull('racikan_ke')->max('racikan_ke') ?? 0;

        $wadah = WadahObat::all();

        // dd($racikans);


        return view('erm.eresep.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'wadah' => $wadah,
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

        $wadah = WadahObat::all();


        return view('erm.eresep.farmasi.create', array_merge([
            'visitation' => $visitation,
            'obats' => $obats,
            'wadah' => $wadah,
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
                'wadah_id'       => $resep->wadah,
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

            // 'diskon' => 'required',
            // 'harga' => 'required',
        ]);

        foreach ($validated['obats'] as $obat) {
            do {
                $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
            } while (ResepFarmasi::where('id', $customId)->exists());

            ResepFarmasi::create([
                'id' => $customId,
                'visitation_id' => $validated['visitation_id'],
                'obat_id' => $obat['obat_id'],
                // 'jumlah' => 1, // atau sesuai jumlah per item racikan jika berbeda
                'aturan_pakai' => $validated['aturan_pakai'],
                'racikan_ke' => $validated['racikan_ke'],
                'wadah_id' => $validated['wadah'],
                'bungkus' => $validated['bungkus'],
                'dosis' => $obat['dosis'],

                // 'diskon' => $validated['diskon'],
                // 'harga' => $validated['harga'],

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

    // SUBMIT RESEP

    public function submitResep(Request $request)
    {
        $visitationId = $request->input('visitation_id');

        // Ambil semua resep terkait
        $reseps = ResepFarmasi::where('visitation_id', $visitationId)->get();

        foreach ($reseps as $resep) {
            Billing::updateOrCreate(
                [
                    'billable_id' => $resep->id,
                    'billable_type' => Resepfarmasi::class,
                ],
                [
                    'visitation_id' => $resep->visitation_id,
                    'jumlah' => $resep->harga,
                    'keterangan' => 'Obat: ' . ($resep->obat->nama ?? 'Tanpa Nama'),
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

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.print', [
            'visitation' => $visitation,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
            'asesmenPenunjang' => $asesmenPenunjang,
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

        // Generate PDF view
        $pdf = PDF::loadView('erm.eresep.farmasi.edukasi-print', [
            'visitation' => $visitation,
            'edukasi' => $edukasi,
            'nonRacikans' => $nonRacikans,
            'racikans' => $racikans,
            'alergis' => $alergis,
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');

        // Return the PDF for download or inline display
        return $pdf->stream('edukasi-obat-' . $visitationId . '.pdf');
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
