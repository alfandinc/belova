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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\VisitationWhatsAppScheduler;

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
            'dokter_id' => 'nullable|exists:erm_dokters,id',
            'tanggal_visitation' => 'required|date',
            // 'waktu_kunjungan' => 'date_format:H:i', // Validasi waktu kunjungan
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
            'jenis_kunjungan' => 'nullable|integer', // allow caller to specify visit type
        ]);

        // Duplicate rule:
        // - jenis_kunjungan = 1 (Konsultasi) => only ONE visitation per pasien per tanggal
        // - jenis_kunjungan = 2/3 (Produk/Lab) => allow multiple
        $jenis = $request->jenis_kunjungan ?? 1;
        if ((int)$jenis === 1) {
            $dokterId = $request->filled('dokter_id') ? $request->dokter_id : null;
            $exists = Visitation::where('pasien_id', $request->pasien_id)
                ->whereDate('tanggal_visitation', $request->tanggal_visitation)
                ->when($dokterId, function ($query, $dokterId) {
                    $query->where('dokter_id', $dokterId);
                }, function ($query) {
                    $query->whereNull('dokter_id');
                })
                ->where('status_kunjungan', '!=', 7)
                ->where(function ($q) {
                    $q->where('jenis_kunjungan', 1)
                      ->orWhereNull('jenis_kunjungan');
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pasien sudah didaftarkan untuk kunjungan hari ini dengan dokter yang sama.'
                ], 422);
            }
        }

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
        $dokterId = $request->filled('dokter_id') ? $request->dokter_id : null;
        $noAntrian = ((int) $jenis === 1 && $dokterId) ? $request->no_antrian : null;

        $visitation = Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $dokterId,
            'tanggal_visitation' => $request->tanggal_visitation,
            'waktu_kunjungan' => $request->waktu_kunjungan, // Menyimpan waktu kunjungan
            'no_antrian' => $noAntrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'jenis_kunjungan' => $jenis,
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

        $waQueue = $this->queueVisitationWhatsApp($visitation);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan berhasil disimpan.',
            'whatsapp' => $waQueue,
        ]);
    }
    public function storeProduk(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'nullable|exists:erm_dokters,id',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
        ]);

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
        $dokterId = $request->filled('dokter_id') ? $request->dokter_id : null;

        $visitation = Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $dokterId,
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

        $waQueue = $this->queueVisitationWhatsApp($visitation);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan berhasil disimpan.',
            'whatsapp' => $waQueue,
        ]);
    }
    public function storeLab(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'nullable|exists:erm_dokters,id',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required', // Add validation for klinik_id
        ]);

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
        $dokterId = $request->filled('dokter_id') ? $request->dokter_id : null;

        $visitation = Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $dokterId,
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

        $waQueue = $this->queueVisitationWhatsApp($visitation);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan berhasil disimpan.',
            'whatsapp' => $waQueue,
        ]);
    }

    public function storeMarketplace(Request $request)
    {
        $validated = $request->validate([
            'pasien_id' => 'nullable|exists:erm_pasiens,id',
            'dokter_id' => 'nullable|exists:erm_dokters,id',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
            'klinik_id' => 'required',
            'force_create_duplicate' => 'nullable|boolean',
            'nama' => [Rule::requiredIf(fn () => !$request->filled('pasien_id')), 'nullable', 'string', 'max:255'],
            'gender' => [Rule::requiredIf(fn () => !$request->filled('pasien_id')), 'nullable', 'in:Laki-laki,Perempuan'],
            'alamat' => [Rule::requiredIf(fn () => !$request->filled('pasien_id')), 'nullable', 'string'],
            'no_hp' => [Rule::requiredIf(fn () => !$request->filled('pasien_id')), 'nullable', 'string', 'max:20'],
            'referral_detail' => [
                Rule::requiredIf(fn () => !$request->filled('pasien_id')),
                'nullable',
                'string',
                Rule::in(Pasien::marketplaceReferralOptions()),
            ],
        ]);

        $forceCreateDuplicate = (bool) ($validated['force_create_duplicate'] ?? false);

        DB::beginTransaction();

        try {
            if (!empty($validated['pasien_id'])) {
                $pasien = Pasien::findOrFail($validated['pasien_id']);
                $this->syncMarketplaceReferralToPasien($pasien, $validated['referral_detail'] ?? null);
            } else {
                $duplicatePasien = $this->findMarketplaceDuplicatePasien(
                    (string) $validated['nama'],
                    (string) $validated['referral_detail']
                );

                if ($duplicatePasien && !$forceCreateDuplicate) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'duplicate' => true,
                        'message' => 'Sudah ada pasien marketplace dengan nama dan referral yang sama.',
                        'pasien' => $this->formatMarketplaceDuplicatePasien($duplicatePasien),
                    ], 409);
                }

                $pasien = $this->createMarketplacePasien($validated);
            }

            $visitation = $this->createMarketplaceVisitation($validated, $pasien->id);

            DB::commit();

            $waQueue = $this->queueVisitationWhatsApp($visitation);

            return response()->json([
                'success' => true,
                'message' => 'Kunjungan marketplace berhasil disimpan.',
                'pasien' => [
                    'id' => $pasien->id,
                    'nama' => $pasien->nama,
                ],
                'whatsapp' => $waQueue,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating marketplace visitation', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kunjungan marketplace.',
            ], 500);
        }
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
            ->orWhereHas('kliniks', function ($query) use ($klinikId) {
                $query->where('erm_klinik.id', $klinikId);
            })
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
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|exists:erm_dokters,id', // dokter tujuan
            // allow dokter_pengirim_id to be nullable: but if present ensure it exists
            'dokter_pengirim_id' => 'nullable|exists:erm_dokters,id',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'nullable|exists:erm_metode_bayar,id',
            // klinik_id will be derived from selected dokter
            'jenis_permintaan' => 'nullable',
            'no_antrian' => 'nullable|integer',
        ]);

        // Duplicate rule for Konsultasi (jenis 1): only ONE visitation per pasien per tanggal
        $exists = Visitation::where('pasien_id', $request->pasien_id)
            ->whereDate('tanggal_visitation', $request->tanggal_visitation)
            ->where('dokter_id', $request->dokter_id)
            ->where('status_kunjungan', '!=', 7)
            ->where(function ($q) {
                $q->where('jenis_kunjungan', 1)
                  ->orWhereNull('jenis_kunjungan');
            })
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien sudah didaftarkan untuk kunjungan hari ini dengan dokter yang sama.'
            ], 422);
        }

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
        // (fix typo: use 'klinik_id')
        $klinikId = $request->klinik_id ?? null;
        if (empty($klinikId)) {
            $dokter = Dokter::find($request->dokter_id);
            if ($dokter) {
                $klinikId = $dokter->klinik_id;
            }
        }

        // Ensure dokter_pengirim_id: if not provided, try to default to the current authenticated user's Dokter record
        $dokterPengirimId = $request->dokter_pengirim_id;
        if (empty($dokterPengirimId)) {
            try {
                $dokterModel = Dokter::where('user_id', Auth::id())->first();
                if ($dokterModel) {
                    $dokterPengirimId = $dokterModel->id;
                }
            } catch (\Exception $e) {
                // ignore and let it be null; Rujuk creation will use whatever value we have
                Log::warning('Unable to auto-resolve dokter_pengirim_id: ' . $e->getMessage());
            }
        }

        // Wrap creation in a transaction to ensure DB integrity and return clear errors
        try {
            DB::beginTransaction();

            $visitation = Visitation::create([
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
                'dokter_pengirim_id' => $dokterPengirimId,
                'dokter_tujuan_id' => $request->dokter_id,
                'jenis_permintaan' => $request->jenis_permintaan,
                'keterangan' => $request->keterangan ?? null,
                'penunjang' => $request->penunjang ?? null,
                'visitation_id' => $customId,
            ]);

            DB::commit();

            $waQueue = $this->queueVisitationWhatsApp($visitation);

            return response()->json([
                'success' => true,
                'message' => 'Rujuk and visitation created successfully.',
                'whatsapp' => $waQueue,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            DB::rollBack();
            Log::warning('Validation failed when creating rujuk', ['error' => $ve->getMessage(), 'request' => $request->all()]);
            throw $ve; // let framework handle returning 422 with errors
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating rujuk/visitation: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Server error while creating rujuk: ' . $e->getMessage()
            ], 500);
        }
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
    // private function sendVisitationWhatsApp($visitation)
    // {
    //     try {
    //         // Load pasien data
    //         $visitation->load(['pasien', 'dokter.user', 'klinik']);
            
    //         // Check if patient has phone number
    //         if (!$visitation->pasien->no_hp) {
    //             Log::info('Patient has no phone number, skipping WhatsApp notification', [
    //                 'visitation_id' => $visitation->id,
    //                 'pasien_id' => $visitation->pasien_id
    //             ]);
    //             return;
    //         }

    //         // Create and dispatch WhatsApp job
    //         SendVisitationWhatsAppNotification::dispatch($visitation->id);
            
    //         Log::info('WhatsApp notification queued for visitation', [
    //             'visitation_id' => $visitation->id,
    //             'pasien_id' => $visitation->pasien_id,
    //             'patient_phone' => $visitation->pasien->no_hp
    //         ]);
            
    //     } catch (\Exception $e) {
    //         Log::error('Error queuing WhatsApp notification for visitation', [
    //             'visitation_id' => $visitation->id,
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }

    /**
     * Test WhatsApp functionality for specific visitation
     */
    // public function testVisitationWhatsApp($id)
    // {
    //     if (!config('whatsapp.enabled')) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'WhatsApp service is disabled'
    //         ]);
    //     }

    //     $whatsappService = new WhatsAppService();
        
    //     // Check service health
    //     $health = $whatsappService->getServiceHealth();
    //     if ($health['status'] !== 'running') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'WhatsApp service is not running: ' . ($health['message'] ?? 'Unknown error')
    //         ]);
    //     }
        
    //     if (!$whatsappService->isConnected()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'WhatsApp service is not connected to WhatsApp Web'
    //         ]);
    //     }

    //     $result = $whatsappService->sendVisitationNotification($id);
        
    //     return response()->json($result);
    // }

    /**
     * Get WhatsApp service status
     */
    public function getWhatsAppStatus()
    {
        $serviceUrl = rtrim(config('app.wa_bot_url', 'http://localhost:3000'), '/');

        try {
            $response = Http::timeout(10)->get($serviceUrl . '/sessions');

            if (!$response->successful()) {
                return response()->json([
                    'enabled' => true,
                    'connected' => false,
                    'message' => 'Failed to reach WhatsApp bot service',
                    'service_url' => $serviceUrl,
                ], 502);
            }

            $sessions = collect($response->json());
            $connected = $sessions->contains(function ($session) {
                return in_array($session['status'] ?? null, ['ready', 'authenticated'], true);
            });

            return response()->json([
                'enabled' => true,
                'connected' => $connected,
                'sessions' => $sessions->values(),
                'service_url' => $serviceUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'enabled' => true,
                'connected' => false,
                'message' => $e->getMessage(),
                'service_url' => $serviceUrl,
            ], 502);
        }
    }

    private function findMarketplaceDuplicatePasien(string $nama, string $referralDetail): ?Pasien
    {
        return Pasien::query()
            ->where('referral_type', Pasien::REFERRAL_TYPE_MARKETPLACE)
            ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower(trim($nama))])
            ->whereRaw('LOWER(TRIM(referral_detail)) = ?', [strtolower(trim($referralDetail))])
            ->first();
    }

    private function formatMarketplaceDuplicatePasien(Pasien $pasien): array
    {
        return [
            'id' => $pasien->id,
            'nama' => $pasien->nama,
            'no_hp' => $pasien->no_hp,
            'alamat' => $pasien->alamat,
            'referral_detail' => $pasien->referral_detail,
        ];
    }

    private function createMarketplacePasien(array $validated): Pasien
    {
        $lastPasienId = DB::table('erm_pasiens')
            ->select(DB::raw('MAX(CAST(id AS UNSIGNED)) as max_id'))
            ->lockForUpdate()
            ->value('max_id');

        $newPasienId = $lastPasienId ? str_pad((int) $lastPasienId + 1, 6, '0', STR_PAD_LEFT) : '000001';

        return Pasien::create([
            'id' => $newPasienId,
            'identity_document' => Pasien::IDENTITY_DOCUMENT_KTP,
            'identity_number' => null,
            'referral_type' => Pasien::REFERRAL_TYPE_MARKETPLACE,
            'referral_detail' => strtolower(trim((string) $validated['referral_detail'])),
            'nama' => trim((string) $validated['nama']),
            'tanggal_lahir' => null,
            'gender' => $validated['gender'],
            'alamat' => trim((string) $validated['alamat']),
            'no_hp' => trim((string) $validated['no_hp']),
            'status_pasien' => 'Regular',
            'status_akses' => 'normal',
            'user_id' => Auth::id(),
        ]);
    }

    private function syncMarketplaceReferralToPasien(Pasien $pasien, ?string $referralDetail): void
    {
        $referralDetail = $referralDetail !== null ? strtolower(trim($referralDetail)) : null;

        if (empty($referralDetail)) {
            return;
        }

        $pasien->forceFill([
            'referral_type' => Pasien::REFERRAL_TYPE_MARKETPLACE,
            'referral_detail' => $referralDetail,
            'user_id' => Auth::id(),
        ])->save();
    }

    private function createMarketplaceVisitation(array $validated, string $pasienId): Visitation
    {
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
        $dokterId = !empty($validated['dokter_id']) ? $validated['dokter_id'] : null;

        $visitation = Visitation::create([
            'id' => $customId,
            'pasien_id' => $pasienId,
            'dokter_id' => $dokterId,
            'tanggal_visitation' => $validated['tanggal_visitation'],
            'waktu_kunjungan' => now()->format('H:i:s'),
            'no_antrian' => null,
            'metode_bayar_id' => $validated['metode_bayar_id'],
            'jenis_kunjungan' => Visitation::TYPE_MARKETPLACE,
            'klinik_id' => $validated['klinik_id'],
            'status_kunjungan' => 2,
            'user_id' => Auth::id(),
        ]);

        \App\Models\ERM\ResepDetail::create([
            'visitation_id' => $customId,
            'no_resep' => 'RSP' . $customId,
            'catatan_dokter' => null,
        ]);

        return $visitation;
    }

    private function queueVisitationWhatsApp(Visitation $visitation): array
    {
        try {
            return app(VisitationWhatsAppScheduler::class)->queueForVisitation($visitation);
        } catch (\Exception $e) {
            Log::error('Error queueing visitation WhatsApp notification', [
                'visitation_id' => $visitation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'queued' => false,
                'reason' => 'queue_exception',
                'message' => 'Pesan WhatsApp tidak dijadwalkan karena terjadi kesalahan internal.',
                'session_status' => 'error',
                'session_note' => $e->getMessage(),
            ];
        }
    }
}
