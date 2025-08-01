<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;
use App\Models\ERM\Dokter;
use App\Models\ERM\MetodeBayar;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class VisitationController extends Controller
{

    public function create()
    {
        $pasiens = Pasien::all();
        return view('erm.visitations.create', compact('pasiens'));
    }

    public function store(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|string',
            'tanggal_visitation' => 'required|date',
            // 'waktu_kunjungan' => 'date_format:H:i', // Validasi waktu kunjungan
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
        ]);

        // Cek apakah pasien sudah didaftarkan di hari yang sama dan dokter yang sama
        $exists = Visitation::where('pasien_id', $request->pasien_id)
            ->whereDate('tanggal_visitation', $request->tanggal_visitation)
            ->where('dokter_id', $request->dokter_id)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien sudah didaftarkan dikunjungan hari ini pada dokter yang sama.'
            ], 422);
        }

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);


        Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'waktu_kunjungan' => $request->waktu_kunjungan, // Menyimpan waktu kunjungan
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'jenis_kunjungan' => 1,
            'klinik_id' => $request->klinik_id, // Add this line to store klinik_id
            'status_kunjungan' => 0,
            'user_id' => Auth::id(), // Menyimpan ID user yang login
        ]);

        // Generate no_resep and create resep detail
        $noResep = 'RSP' . $customId;
        \App\Models\ERM\ResepDetail::create([
            'visitation_id' => $customId,
            'no_resep' => $noResep,
            'catatan_dokter' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Kunjungan berhasil disimpan.']);
    }
    public function storeProduk(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|string',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
        ]);

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);


        Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            // 'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'klinik_id' => $request->klinik_id, // Add this line to store klinik_id
            'status_kunjungan' => 2,
            'jenis_kunjungan' => 2,
            'user_id' => Auth::id(), // Menyimpan ID user yang login
        ]);

        // Generate no_resep and create resep detail
        $noResep = 'RSP' . $customId;
        \App\Models\ERM\ResepDetail::create([
            'visitation_id' => $customId,
            'no_resep' => $noResep,
            'catatan_dokter' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Kunjungan berhasil disimpan.']);
    }
    public function storeLab(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|string',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
        ]);

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);


        Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            // 'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'klinik_id' => $request->klinik_id, // Add this line to store klinik_id
            'status_kunjungan' => 2,
            'jenis_kunjungan' => 3,
            'user_id' => Auth::id(), // Menyimpan ID user yang login
        ]);

        // Generate no_resep and create resep detail
        $noResep = 'RSP' . $customId;
        \App\Models\ERM\ResepDetail::create([
            'visitation_id' => $customId,
            'no_resep' => $noResep,
            'catatan_dokter' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Kunjungan berhasil disimpan.']);
    }

    public function cekAntrian(Request $request)
    {
        $tanggal = $request->tanggal;
        $dokter_id = $request->dokter_id;

        // Get the current max antrian number for the date and doctor
        $max = Visitation::whereDate('tanggal_visitation', $tanggal)
            ->where('dokter_id', $dokter_id)
            ->max('no_antrian');

        // Numbers to skip
        $skip = [3, 5];
        $next = ($max ?? 0) + 1;
        while (in_array($next, $skip)) {
            $next++;
        }

        return response()->json([
            'no_antrian' => $next
        ]);
    }

    public function getDoktersByKlinik($klinikId)
    {
        // // Add logging to see what's happening
        // \Log::info("Finding doctors for klinik_id: " . $klinikId);

        // // First check if any doctors exist with this klinik_id
        // $count = Dokter::where('klinik_id', $klinikId)->count();
        // \Log::info("Number of doctors found: " . $count);

        $dokters = Dokter::where('klinik_id', $klinikId)
            ->with(['spesialisasi', 'user'])
            ->get();

        return response()->json($dokters);
    }

    /**
     * Temporary method to generate missing resep detail for existing visitations.
     * Remove after running once.
     */
    public function generateMissingResepDetails()
    {
        $visitations = \App\Models\ERM\Visitation::all();
        $created = 0;
        foreach ($visitations as $visitation) {
            $exists = \App\Models\ERM\ResepDetail::where('visitation_id', $visitation->id)->exists();
            if (!$exists) {
                $noResep = 'RSP' . $visitation->id;
                \App\Models\ERM\ResepDetail::create([
                    'visitation_id' => $visitation->id,
                    'no_resep' => $noResep,
                    'catatan_dokter' => null,
                ]);
                $created++;
            }
        }
        return response()->json([
            'message' => "Created $created missing resep detail records."
        ]);
    }
}
