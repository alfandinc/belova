<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\AsesmenPerawat;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Auth;

class AsesmenPerawatController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        // Cek apakah asesmen sudah ada
        $asesmen = AsesmenPerawat::where('visitation_id', $visitationId)->first();
        return view('erm.asesmenperawat.create', compact('visitation'));
    }

    public function store(Request $request)
    {
        $user = auth()->id();

        $visitation = Visitation::findOrFail($request->visitation_id); // Find the visitation by ID
        $visitation->progress = 2; // Change progress to 2
        $visitation->status_dokumen = 'Asesmen';
        $visitation->save(); // Save the updated visitation

        AsesmenPerawat::create([
            'visitation_id' => $request->visitation_id,
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

        ]);

        return redirect()->route('erm.asesmenperawat.create', $request->visitation_id)
            ->with('success', 'Data Asesmen berhasil ditambahkan.');
    }
}
