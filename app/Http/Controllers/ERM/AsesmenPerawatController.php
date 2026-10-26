<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\AsesmenPerawat;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\SaveErrorMessage;

class AsesmenPerawatController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $dataperawat = AsesmenPerawat::where('visitation_id', $visitationId)->first();

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.asesmenperawat.create', array_merge([
            'visitation' => $visitation,
            'dataperawat' => $dataperawat,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        // Ambil visitation & pasien untuk cek umur
        $visitation = Visitation::findOrFail($request->visitation_id);
        $pasien = $visitation->pasien;

        // Hitung umur pasien (tahun). Jika tanggal_lahir tidak tersedia, anggap dewasa false (umur 0)
        $age = 0;
        if ($pasien && $pasien->tanggal_lahir) {
            try {
                $age = Carbon::parse($pasien->tanggal_lahir)->age;
            } catch (\Exception $e) {
                $age = 0;
            }
        }

        // Validasi input wajib umum
        $rules = [
            'keluhan_utama' => 'required',
            'alasan_kunjungan' => 'required',
            'kesadaran' => 'required',
        ];

        // Jika pasien > 17 tahun, jadikan vitals wajib dan tambahkan aturan format/range
        if ($age > 17) {
            $rules = array_merge($rules, [
                'td' => ['required', 'regex:/^\d{2,3}\/\d{2,3}$/'],
                'nadi' => 'required|integer|between:40,140',
                'rr' => 'required|integer|between:15,60',
                'suhu' => 'required|numeric|between:35,41',
            ]);
        } else {
            // Untuk pasien <=17 tahun, terima nilai vitals jika ada, tapi tidak wajib.
            $rules = array_merge($rules, [
                'td' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'],
                'nadi' => 'nullable|integer|between:40,140',
                'rr' => 'nullable|integer|between:15,60',
                'suhu' => 'nullable|numeric|between:35,41',
            ]);
        }

        $validated = $request->validate($rules);

    $user = Auth::id();

        // Tangani masalah keperawatan
        $masalah = $request->input('masalah_keperawatan', []); // checkbox array

        // Jika "Lain-lain" dipilih, dan ada teksnya, ubah dari "Lain-lain" jadi "Lain-lain: teks"
        if (in_array('Lain-lain', $masalah) && $request->lain_lain_text) {
            $masalah = array_map(function ($item) use ($request) {
                return $item === 'Lain-lain' ? 'Lain-lain: ' . $request->lain_lain_text : $item;
            }, $masalah);
        }

        // gunakan $visitation yang sudah diambil di atas
        // Progress only moves forward: editing the asesmen after the dokter finished (status 2 / CPPT)
        // must not send the visit back (it would drop out of Billing).
        $visitation->status_kunjungan = Visitation::advanceStatus($visitation->status_kunjungan, 1);
        $visitation->status_dokumen = $visitation->status_dokumen ?: 'asesmen';

        // "Tindakan saja" (Konsultasi only): the perawat skips the dokter's asesmen, so the visit is done
        // here and reaches Billing. Not stored anywhere, so it is never undone here: the dokter can still
        // fill the asesmen of a skipped visit.
        $lewatiDokter = (int) $visitation->jenis_kunjungan === Visitation::TYPE_KONSULTASI
            && $request->boolean('lewati_asesmen_dokter');
        if ($lewatiDokter) {
            $visitation->status_kunjungan = Visitation::advanceStatus($visitation->status_kunjungan, 2);
        }
        // Visit status + asesmen together; a failure returns a readable message for the Swal
        try {
            $asesmenperawat = DB::transaction(function () use ($request, $visitation, $user, $masalah) {
                $visitation->save(); // Save the updated visitation

                return AsesmenPerawat::updateOrCreate(
                    ['visitation_id' => $request->visitation_id],
                    [
                        'user_id' => $user,
                        'keluhan_utama' => $request->keluhan_utama,
                        'alasan_kunjungan' => $request->alasan_kunjungan,
                        'kesadaran' => $request->kesadaran,
                        'td' => $request->td,
                        'nadi' => $request->nadi,
                        'rr' => $request->rr,
                        'suhu' => $request->suhu,
                        'riwayat_psikososial' => $request->riwayat_psikososial,
                        'tb' => $request->tb,
                        'bb' => $request->bb,
                        'lla' => $request->lla,
                        'diet' => $request->diet,
                        'porsi' => $request->porsi,
                        'imt' => $request->imt,
                        'presentase' => $request->presentase,
                        'efek' => $request->efek,
                        'nyeri' => $request->nyeri,
                        'p' => $request->p,
                        'q' => $request->q,
                        'r' => $request->r,
                        't' => $request->t,
                        'onset' => $request->onset,
                        'skor' => $request->skor,
                        'kategori' => $request->kategori,
                        'kategori_risja' => $request->kategori_risja,
                        'status_fungsional' => $request->status_fungsional,
        
                        'masalah_keperawatan' => $masalah,
                    ]
                );
            });
        } catch (\Throwable $e) {
            Log::error('Simpan asesmen perawat gagal: ' . $e->getMessage(), ['visitation_id' => $request->visitation_id]);
            return response()->json(['status' => 'error', 'message' => SaveErrorMessage::from($e)], 500);
        }

        $message = ($asesmenperawat->wasRecentlyCreated)
            ? 'Asesmen Perawat berhasil dibuat.'
            : 'Asesmen Perawat berhasil diperbarui.';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'tindakan_url' => $lewatiDokter ? route('erm.tindakan.create', $visitation->id) : null,
        ]);
    }
}
