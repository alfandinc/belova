<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPerawat;
use App\Models\ERM\AsesmenDalam;
use App\Models\ERM\AsesmenPenunjang;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Alergi;

use App\Models\ERM\Diagnosa;

class AsesmenController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $dataperawat = AsesmenPerawat::where('visitation_id', $visitationId)->first();

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.asesmendokter.create', array_merge([
            'visitation' => $visitation,
            'dataperawat' => $dataperawat,
        ], $pasienData, $createKunjunganData));
    }


    public function store(Request $request)
    {
        // Simpan data asesmen dalam
        $asesmenDalam = AsesmenDalam::create([
            'visitation_id' => $request->visitation_id,
            'keluhan_utama' => $request->keluhan_utama,
            'riwayat_penyakit_sekarang' => $request->riwayat_penyakit_sekarang,
            'riwayat_penyakit_dahulu' => $request->riwayat_penyakit_dahulu,
            'obat_dikonsumsi' => $request->obat_dikonsumsi,
            'keadaan_umum' => $request->keadaan_umum,
            'e' => $request->e,
            'v' => $request->v,
            'm' => $request->m,
            'hsl' => $request->hsl,
            'td' => $request->td,
            'n' => $request->n,
            's' => $request->s,
            'r' => $request->r,
            'kepala' => $request->kepala,
            'leher' => $request->leher,
            'thorax' => $request->thorax,
            'abdomen' => $request->abdomen,
            'genitalia' => $request->genitalia,
            'ext_atas' => $request->ext_atas,
            'ext_bawah' => $request->ext_bawah,
            'status_lokalis' => $request->status_lokalis,
            'ket_status_lokalis' => $request->ket_status_lokalis,
        ]);

        // Simpan data asesmen penunjang
        AsesmenPenunjang::create([
            'visitation_id' => $request->visitation_id,
            'diagnosa_kerja_1' => $request->diagnosa_kerja_1,
            'diagnosa_kerja_2' => $request->diagnosa_kerja_2,
            'diagnosa_kerja_3' => $request->diagnosa_kerja_3,
            'diagnosa_kerja_4' => $request->diagnosa_kerja_4,
            'diagnosa_kerja_5' => $request->diagnosa_kerja_5,
            'diagnosa_banding' => $request->diagnosa_banding,
            'masalah_medis' => $request->masalah_medis,
            'masalah_keperawatan' => $request->masalah_keperawatan,
            'sasaran' => $request->sasaran,
            'standing_order' => $request->standing_order,
            'rtl' => $request->rtl,
            'ruang' => $request->ruang,
            'dpip' => $request->dpip,
            'pengantar' => $request->pengantar,
            'rujuk' => $request->has('rujuk') ? json_encode($request->rujuk) : null, // hanya simpan jika ada input
            'kontrol_homecare' => $request->kontrol_homecare,
            'tanggal_kontrol' => $request->tanggal_kontrol,
            'edukasi' => $request->has('edukasi') ? json_encode($request->edukasi) : null, // hanya simpan jika ada input
            'nama_keluarga' => $request->nama_keluarga,
            'hubungan_keluarga' => $request->hubungan_keluarga,
            'alasan_tidak_edukasi' => $request->alasan_tidak_edukasi,
        ]);

        $visitation = Visitation::findOrFail($request->visitation_id); // Find the visitation by ID
        $visitation->progress = 3; // Change progress to 2
        $visitation->save(); // Save the updated visitation

        return redirect()->back()->with('success', 'Asesmen berhasil disimpan');
    }
}
