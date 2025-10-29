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
use Illuminate\Support\Facades\Log;
use App\Jobs\SendVisitationWhatsAppNotification;
use App\Services\WhatsAppService;

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

        $visitation = Visitation::create([
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

        // Send WhatsApp notification if enabled
        if (config('whatsapp.enabled')) {
            $this->sendVisitationWhatsApp($visitation);
        }

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

        $visitation = Visitation::create([
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

        // Send WhatsApp notification if enabled
        if (config('whatsapp.enabled')) {
            $this->sendVisitationWhatsApp($visitation);
        }

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

        $visitation = Visitation::create([
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

        // Send WhatsApp notification if enabled
        if (config('whatsapp.enabled')) {
            $this->sendVisitationWhatsApp($visitation);
        }

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
     * Store visitation and rujuk record for referral (rujuk).
     */
    public function storeRujuk(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'dokter_id' => 'required', // dokter tujuan
            'dokter_pengirim_id' => 'required',
            'tanggal_visitation' => 'required',
            'metode_bayar_id' => 'nullable',
            // klinik_id will be derived from selected dokter
            'jenis_permintaan' => 'nullable',
            'no_antrian' => 'nullable',
        ]);

        // Create visitation similar to store()
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);

        // Determine no_antrian: use provided or compute next available
        $noAntrian = $request->no_antrian;
        if (empty($noAntrian)) {
            $max = Visitation::whereDate('tanggal_visitation', $request->tanggal_visitation)
                ->where('dokter_id', $request->dokter_id)
                ->max('no_antrian');
            $skip = [3,5];
            $next = ($max ?? 0) + 1;
            while (in_array($next, $skip)) {
                $next++;
            }
            $noAntrian = $next;
        }

        // Determine klinik: if request provides klinik_id use it, otherwise derive from dokter
        $klinikId = $request->klinink_id ?? null;
        if (empty($klinikId)) {
            $dokter = Dokter::find($request->dokter_id);
            if ($dokter) {
                $klinikId = $dokter->klinik_id;
            }
        }

        Visitation::create([
            'id' => $customId,
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'waktu_kunjungan' => $request->waktu_kunjungan ?? null,
            'no_antrian' => $noAntrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'klinik_id' => $klinikId,
            'status_kunjungan' => 0,
            'jenis_kunjungan' => 1,
            'user_id' => Auth::id(),
        ]);

        // create resep detail
        $noResep = 'RSP' . $customId;
        \App\Models\ERM\ResepDetail::create([
            'visitation_id' => $customId,
            'no_resep' => $noResep,
            'catatan_dokter' => null,
        ]);

        // Create rujuk record
        \App\Models\ERM\Rujuk::create([
            'pasien_id' => $request->pasien_id,
            'dokter_pengirim_id' => $request->dokter_pengirim_id,
            'dokter_tujuan_id' => $request->dokter_id,
            'jenis_permintaan' => $request->jenis_permintaan,
            'keterangan' => $request->keterangan ?? null,
            'penunjang' => $request->penunjang ?? null,
            'visitation_id' => $customId,
        ]);

        return response()->json(['success' => true, 'message' => 'Rujuk and visitation created successfully.']);
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

    /**
     * Send WhatsApp notification for new visitation
     */
    private function sendVisitationWhatsApp($visitation)
    {
        try {
            // Load pasien data
            $visitation->load(['pasien', 'dokter.user', 'klinik']);
            
            // Check if patient has phone number
            if (!$visitation->pasien->no_hp) {
                Log::info('Patient has no phone number, skipping WhatsApp notification', [
                    'visitation_id' => $visitation->id,
                    'pasien_id' => $visitation->pasien_id
                ]);
                return;
            }

            // Create and dispatch WhatsApp job
            SendVisitationWhatsAppNotification::dispatch($visitation->id);
            
            Log::info('WhatsApp notification queued for visitation', [
                'visitation_id' => $visitation->id,
                'pasien_id' => $visitation->pasien_id,
                'patient_phone' => $visitation->pasien->no_hp
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error queuing WhatsApp notification for visitation', [
                'visitation_id' => $visitation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test WhatsApp functionality for specific visitation
     */
    public function testVisitationWhatsApp($id)
    {
        if (!config('whatsapp.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is disabled'
            ]);
        }

        $whatsappService = new WhatsAppService();
        
        // Check service health
        $health = $whatsappService->getServiceHealth();
        if ($health['status'] !== 'running') {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is not running: ' . ($health['message'] ?? 'Unknown error')
            ]);
        }
        
        if (!$whatsappService->isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is not connected to WhatsApp Web'
            ]);
        }

        $result = $whatsappService->sendVisitationNotification($id);
        
        return response()->json($result);
    }

    /**
     * Get WhatsApp service status
     */
    public function getWhatsAppStatus()
    {
        if (!config('whatsapp.enabled')) {
            return response()->json([
                'enabled' => false,
                'connected' => false,
                'message' => 'WhatsApp service is disabled'
            ]);
        }

        $whatsappService = new WhatsAppService();
        $health = $whatsappService->getServiceHealth();
        $connected = $whatsappService->isConnected();
        
        return response()->json([
            'enabled' => true,
            'connected' => $connected,
            'health' => $health,
            'service_url' => config('whatsapp.service_url')
        ]);
    }
}
