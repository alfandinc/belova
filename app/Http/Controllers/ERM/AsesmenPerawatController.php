<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\AsesmenPerawat;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;

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
        $user = auth()->id();

        // Tangani masalah keperawatan
        $masalah = $request->input('masalah_keperawatan', []); // checkbox array

        // Jika "Lain-lain" dipilih, dan ada teksnya, ubah dari "Lain-lain" jadi "Lain-lain: teks"
        if (in_array('Lain-lain', $masalah) && $request->lain_lain_text) {
            $masalah = array_map(function ($item) use ($request) {
                return $item === 'Lain-lain' ? 'Lain-lain: ' . $request->lain_lain_text : $item;
            }, $masalah);
        }


        $visitation = Visitation::findOrFail($request->visitation_id); // Find the visitation by ID
        $visitation->status_kunjungan = 1; // Change progress to 1
        $visitation->status_dokumen = 'asesmen';
        $visitation->save(); // Save the updated visitation

        $asesmenperawat = AsesmenPerawat::updateOrCreate(
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

        $message = ($asesmenperawat->wasRecentlyCreated)
            ? 'Asesmen Perawat berhasil dibuat.'
            : 'Asesmen Perawat berhasil diperbarui.';

        return response()->json([
            'status' => 'success',
            'message' => $message
        ]);
    }
}
