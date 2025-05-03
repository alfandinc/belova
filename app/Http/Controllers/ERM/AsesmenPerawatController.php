<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\AsesmenPerawat;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Alergi;
use Illuminate\Support\Carbon;

class AsesmenPerawatController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        // Cek apakah asesmen sudah ada
        $asesmen = AsesmenPerawat::where('visitation_id', $visitationId)->first();

        $tanggal_lahir = $visitation->pasien->tanggal_lahir;
        $usia = '-';

        if ($tanggal_lahir) {
            $dob = Carbon::parse($tanggal_lahir);
            $now = Carbon::now();
            // Menghitung tahun
            $years = $dob->diffInYears($now);
            // Menghitung bulan
            $dob = $dob->addYears($years);
            $months = $dob->diffInMonths($now);
            // Menghitung hari
            $dob = $dob->addMonths($months);
            $days = $dob->diffInDays($now);
            // Mengubah semua hasil ke format integer untuk menghindari desimal
            $years = (int) $years;
            $months = (int) $months;
            $days = (int) $days;
            // Format hasil usia
            $usia = "$years tahun $months bulan $days hari";
        }

        $pasien = $visitation->pasien;

        $zatAktif = ZatAktif::all();
        $alergiPasien = Alergi::where('pasien_id', $pasien->id)
            ->with('zataktif') // Eager load zataktif relationship
            ->get();

        // Extract zataktif IDs and names
        $alergiNames = $alergiPasien->pluck('zataktif.nama')->toArray();
        $alergiIds = $alergiPasien->pluck('zataktif_id')->toArray();

        $alergistatus = $alergiPasien->first()->status;
        $alergikatakunci = $alergiPasien->first()->katakunci;
        return view('erm.asesmenperawat.create', compact('visitation', 'usia', 'alergiNames', 'alergiIds', 'zatAktif', 'alergistatus', 'alergikatakunci'));
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
