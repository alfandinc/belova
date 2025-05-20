<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;

use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPerawat;
use App\Models\ERM\AsesmenDalam;
use App\Models\ERM\AsesmenPenunjang;

class AsesmenController extends Controller
{
    public function create($visitationId)
    {
        // $visitation = Visitation::findOrFail($visitationId);
        $visitation = Visitation::with('dokter.spesialisasi')->findOrFail($visitationId);
        $dataperawat = AsesmenPerawat::where('visitation_id', $visitationId)->first();
        $asesmenDalam = AsesmenDalam::where('visitation_id', $visitationId)->first();
        $asesmenPenunjang = AsesmenPenunjang::where('visitation_id', $visitationId)->first();

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);


        // dd($visitation->dokter->spesialisasi->nama);

        return view('erm.asesmendokter.create', array_merge([
            'visitation' => $visitation,
            'dataperawat' => $dataperawat,
            'asesmenDalam' => $asesmenDalam,
            'asesmenPenunjang' => $asesmenPenunjang,
        ], $pasienData, $createKunjunganData));
    }


    public function store(Request $request)
    {
        if ($request->has('status_lokalis_image')) {
            $base64Image = $request->status_lokalis_image;

            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception('Invalid image type');
                }

                $data = base64_decode($data);
                if ($data === false) {
                    throw new \Exception('Base64 decode failed');
                }

                // Delete old image if exists
                $existing = AsesmenDalam::where('visitation_id', $request->visitation_id)->first();
                if ($existing && $existing->status_lokalis && file_exists(public_path($existing->status_lokalis))) {
                    unlink(public_path($existing->status_lokalis));
                }

                $filename = 'lokalis_' . $request->visitation_id . '.' . $type;
                $path = public_path("img/hasilassesmen/" . $filename);

                // Ensure the directory exists
                if (!file_exists(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                file_put_contents($path, $data);

                // Save relative path
                $request->merge([
                    'status_lokalis' => "img/hasilassesmen/{$filename}"
                ]);
            }
        }
        $dalam = AsesmenDalam::updateOrCreate(
            ['visitation_id' => $request->visitation_id], // key to check
            [ // fields to insert or update
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
                'status_lokalis' => $request->status_lokalis, // now contains the image path
                'ket_status_lokalis' => $request->ket_status_lokalis,
            ]
        );

        $penunjang = AsesmenPenunjang::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'diagnosakerja_1' => $request->diagnosakerja_1,
                'diagnosakerja_2' => $request->diagnosakerja_2,
                'diagnosakerja_3' => $request->diagnosakerja_3,
                'diagnosakerja_4' => $request->diagnosakerja_4,
                'diagnosakerja_5' => $request->diagnosakerja_5,
                'diagnosa_banding' => $request->diagnosa_banding,
                'masalah_medis' => $request->masalah_medis,
                'masalah_keperawatan' => $request->masalah_keperawatan,
                'sasaran' => $request->sasaran,
                'standing_order' => $request->standing_order,
                'rtl' => $request->rtl,
                'ruang' => $request->ruang,
                'dpip' => $request->dpip,
                'pengantar' => $request->pengantar,
                'rujuk' => $request->filled('rujuk') ? json_encode($request->rujuk) : null,
                'kontrol_homecare' => $request->kontrol_homecare,
                'tanggal_kontrol' => $request->tanggal_kontrol,
                'edukasi' => $request->filled('edukasi') ? json_encode($request->edukasi) : null,
                'nama_keluarga' => $request->nama_keluarga,
                'hubungan_keluarga' => $request->hubungan_keluarga,
                'alasan_tidak_edukasi' => $request->alasan_tidak_edukasi,
            ]
        );

        Visitation::where('id', $request->visitation_id)->update(['progress' => 3]);

        $message = ($dalam->wasRecentlyCreated && $penunjang->wasRecentlyCreated)
            ? 'Asesmen Dokter berhasil dibuat.'
            : 'Asesmen Dokter berhasil diperbarui.';

        return response()->json([
            'status' => 'success',
            'message' => $message
        ]);
    }
}
