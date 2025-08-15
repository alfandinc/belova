<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\AsesmenAnak;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPerawat;
use App\Models\ERM\AsesmenDalam;
use App\Models\ERM\AsesmenPenunjang;
use App\Models\ERM\AsesmenUmum;
use App\Models\ERM\AsesmenEstetika;
use App\Models\ERM\AsesmenGigi;
use App\Models\ERM\AsesmenSaraf;
use App\Models\Finance\Billing;
use App\Models\ERM\JasaMedis;
use App\Models\ERM\Konsultasi;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class AsesmenController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::with('dokter.spesialisasi')->findOrFail($visitationId);
        $dataperawat = AsesmenPerawat::where('visitation_id', $visitationId)->first();
        $asesmenPenunjang = AsesmenPenunjang::where('visitation_id', $visitationId)->first();

        $spesialisasi = Str::slug($visitation->dokter->spesialisasi->nama, '_');

        $asesmen = [
            'penyakit_dalam' => AsesmenDalam::where('visitation_id', $visitationId)->first(),
            'umum' => AsesmenUmum::where('visitation_id', $visitationId)->first(),
            'estetika' => AsesmenEstetika::where('visitation_id', $visitationId)->first(),
            'anak' => AsesmenAnak::where('visitation_id', $visitationId)->first(),
            'saraf' => AsesmenSaraf::where('visitation_id', $visitationId)->first(),
            'gigi' => AsesmenGigi::where('visitation_id', $visitationId)->first(),
        ];
        $currentAsesmen = $asesmen[$spesialisasi] ?? null;

        // Lokalis image logic
        $lokalisDefaults = [
            'penyakit_dalam' => 'img/asesmen/dalam.png',
            'estetika' => 'img/asesmen/estetika.png',
            'gigi' => 'img/asesmen/gigi.png',
        ];
        $lokalisPath = old('status_lokalis', $currentAsesmen->status_lokalis ?? null);
        $lokalisBackground = $lokalisPath ?: ($lokalisDefaults[$spesialisasi] ?? 'img/lokalis/default.png');

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        $jenisKonsultasi = Konsultasi::get();

        // Get previous visitation for this patient (exclude current)
        $lastVisitation = Visitation::where('pasien_id', $visitation->pasien_id)
            ->where('id', '!=', $visitationId)
            ->orderByDesc('tanggal_visitation')
            ->orderByDesc('waktu_kunjungan')
            ->first();

        $lastAsesmenDalam = null;
        if ($lastVisitation) {
            $lastAsesmenDalam = AsesmenDalam::where('visitation_id', $lastVisitation->id)->first();
        }

        // Prepare prefill values for penyakit_dalam fields
        $prefill_riwayat_penyakit_dahulu = $lastAsesmenDalam ? $lastAsesmenDalam->riwayat_penyakit_dahulu : '';
        $prefill_obat_dikonsumsi = $lastAsesmenDalam ? $lastAsesmenDalam->obat_dikonsumsi : '';

        return view('erm.asesmendokter.create', array_merge([
            'visitation' => $visitation,
            'dataperawat' => $dataperawat,
            'asesmenPenunjang' => $asesmenPenunjang,
            'currentAsesmen' => $currentAsesmen,
            'spesialisasi' => $spesialisasi,
            'jenisKonsultasi' => $jenisKonsultasi,
            'lokalisPath' => $lokalisPath,
            'lokalisBackground' => $lokalisBackground,
            'prefill_riwayat_penyakit_dahulu' => $prefill_riwayat_penyakit_dahulu,
            'prefill_obat_dikonsumsi' => $prefill_obat_dikonsumsi,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        // Get spesialisasi from visitation (safe from manipulation)
        $visitation = Visitation::with('dokter.spesialisasi')->findOrFail($request->visitation_id);
        $spesialisasi = strtolower($visitation->dokter->spesialisasi->nama);

        // Call spesialisasi-based save function
        if ($spesialisasi === 'penyakit dalam') {
            $this->storeAsesmenDalam($request);
        } elseif ($spesialisasi === 'umum') {
            $this->storeAsesmenUmum($request);
        } elseif ($spesialisasi === 'estetika') {
            $this->storeAsesmenEstetika($request);
        } elseif ($spesialisasi === 'anak') {
            $this->storeAsesmenAnak($request);
        } elseif ($spesialisasi === 'saraf') {
            $this->storeAsesmenSaraf($request);
        } elseif ($spesialisasi === 'gigi') {
            $this->storeAsesmenGigi($request);
        }

        // Shared Penunjang logic
        $this->storeAsesmenPenunjang($request);

        $this->storeJenisKonsultasi($request);

        Visitation::where('id', $request->visitation_id)->update(['status_kunjungan' => 2]);

        return response()->json([
            'status' => 'success',
            'message' => 'Asesmen Dokter berhasil disimpan.'
        ]);
    }

    private function storeAsesmenDalam(Request $request)
    {
        if ($request->has('status_lokalis_image')) {
            $this->saveLokalisImage($request);
        }

        AsesmenDalam::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
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
            ]
        );
    }

    private function storeAsesmenUmum(Request $request)
    {
        if ($request->has('status_lokalis_image')) {
            $this->saveLokalisImage($request);
        }

        AsesmenUmum::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
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
            ]
        );
    }

    private function storeAsesmenEstetika(Request $request)
    {
        // Check if a new image is provided
        if ($request->has('status_lokalis_image') && !empty($request->status_lokalis_image)) {
            $this->saveLokalisImage($request);
        }

        // Retain the existing image if no new image is provided
        $existingAsesmen = AsesmenEstetika::where('visitation_id', $request->visitation_id)->first();
        $statusLokalis = $existingAsesmen->status_lokalis ?? null;

        AsesmenEstetika::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'autoanamnesis' => $request->autoanamnesis,
                'alloanamnesis' => $request->alloanamnesis,
                'anamnesis1' => $request->anamnesis1,
                'anamnesis2' => $request->anamnesis2,
                'keluhan_utama' => $request->keluhan_utama,
                'riwayat_penyakit_sekarang' => $request->riwayat_penyakit_sekarang,
                'allo_dengan' => $request->allo_dengan,
                'hasil_allo' => $request->hasil_allo,
                'riwayat_penyakit_dahulu' => $request->riwayat_penyakit_dahulu,
                'obat_dikonsumsi' => $request->obat_dikonsumsi,
                'keadaan_umum' => $request->keadaan_umum,
                'td' => $request->td,
                'n' => $request->n,
                'r' => $request->r,
                's' => $request->s,
                'kebiasaan_makan' => $request->filled('kebiasaan_makan') ? json_encode($request->kebiasaan_makan) : null, // Convert array to JSON
                'kebiasaan_minum' => $request->filled('kebiasaan_minum') ? json_encode($request->kebiasaan_minum) : null, // Convert array to JSON
                'pola_tidur' => $request->pola_tidur,
                'kontrasepsi' => $request->kontrasepsi,
                'riwayat_perawatan' => $request->riwayat_perawatan,
                'jenis_kulit' => $request->jenis_kulit,
                'kelembaban' => $request->kelembaban,
                'kekenyalan' => $request->kekenyalan,
                'area_kerutan' => $request->filled('area_kerutan') ? json_encode($request->area_kerutan) : null, // Convert array to JSON
                'kelainan_kulit' => $request->filled('kelainan_kulit') ? json_encode($request->kelainan_kulit) : null, // Convert array to JSON
                'anjuran' => $request->anjuran,
                'status_lokalis' => $request->status_lokalis ?? $statusLokalis,
                'ket_status_lokalis' => $request->ket_status_lokalis,
            ]
        );
    }

    private function storeAsesmenAnak(Request $request)
    {
        // Check if a new image is provided
        if ($request->has('status_lokalis_image') && !empty($request->status_lokalis_image)) {
            $this->saveLokalisImage($request);
        }

        // Retain the existing image if no new image is provided
        $existingAsesmen = AsesmenAnak::where('visitation_id', $request->visitation_id)->first();
        $statusLokalis = $existingAsesmen->status_lokalis ?? null;

        AsesmenAnak::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'autoanamnesis' => $request->autoanamnesis,
                'alloanamnesis' => $request->alloanamnesis,
                'anamnesis1' => $request->anamnesis1,
                'anamnesis2' => $request->anamnesis2,
                'keluhan_utama' => $request->keluhan_utama,
                'riwayat_penyakit_sekarang' => $request->riwayat_penyakit_sekarang,
                'allo_dengan' => $request->allo_dengan,
                'hasil_allo' => $request->hasil_allo,
                'riwayat_penyakit_dahulu' => $request->riwayat_penyakit_dahulu,
                'obat_dikonsumsi' => $request->obat_dikonsumsi,
                'riwayat_penyakit_keluarga' => $request->riwayat_penyakit_keluarga,
            'riwayat_makanan' => $request->riwayat_makanan,
            'riwayat_tumbang' => $request->riwayat_tumbang,
            'riwayat_kehamilan' => $request->riwayat_kehamilan,
            'riwayat_persalinan' => $request->riwayat_persalinan,
            'e' => $request->e,
            'v' => $request->v,
            'm' => $request->m,
            'hsl' => $request->hsl,
                'keadaan_umum' => $request->keadaan_umum,
                'imunisasi_dasar' => $request->imunisasi_dasar,
                'imunisasi_dasar_ket' => $request->imunisasi_dasar_ket,
                'imunisasi_lanjut' => $request->imunisasi_lanjut,
                'imunisasi_lanjut_ket' => $request->imunisasi_lanjut_ket,
                'td' => $request->td,
                'n' => $request->n,
                'r' => $request->r,
                's' => $request->s,
                'gizi' => $request->gizi,
                'bb' => $request->bb,
                'tb' => $request->tb,
                'lk' => $request->lk,
                'kepala' => $request->kepala,
                'leher' => $request->leher,
                'thorax' => $request->thorax,
                'jantung' => $request->jantung,
                'paru' => $request->paru,
                'abdomen' => $request->abdomen,
                'genitalia' => $request->genitalia,
                'extremitas' => $request->extremitas,
                'pemeriksaan_fisik_tambahan' => $request->pemeriksaan_fisik_tambahan,

            ]
        );
    }

    private function storeAsesmenSaraf(Request $request)
    {
        // Check if a new image is provided
        if ($request->has('status_lokalis_image') && !empty($request->status_lokalis_image)) {
            $this->saveLokalisImage($request);
        }

        // Retain the existing image if no new image is provided
        $existingAsesmen = AsesmenSaraf::where('visitation_id', $request->visitation_id)->first();
        $statusLokalis = $existingAsesmen->status_lokalis ?? null;

        AsesmenSaraf::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'autoanamnesis' => $request->autoanamnesis,
                'alloanamnesis' => $request->alloanamnesis,
                'anamnesis1' => $request->anamnesis1,
                'anamnesis2' => $request->anamnesis2,
                'keluhan_utama' => $request->keluhan_utama,
                'riwayat_penyakit_sekarang' => $request->riwayat_penyakit_sekarang,
                'allo_dengan' => $request->allo_dengan,
                'hasil_allo' => $request->hasil_allo,
                'riwayat_penyakit_dahulu' => $request->riwayat_penyakit_dahulu,
                'obat_dikonsumsi' => $request->obat_dikonsumsi,
                'keadaan_umum' => $request->keadaan_umum,
                'td' => $request->td,
                'n' => $request->n,
                'r' => $request->r,
                's' => $request->s,
                'e' => $request->e,
                'm' => $request->m,
                'hsl' => $request->hsl,
                'vas' => $request->vas,
                'diameter_ket' => $request->diameter_ket,
                'diameter_1' => $request->diameter_1,
                'diameter_2' => $request->diameter_2,
                'isokor' => $request->isokor,
                'anisokor' => $request->anisokor,
                'reflek_cahaya' => $request->reflek_cahaya,
                'reflek_cahaya1' => $request->reflek_cahaya1,
                'reflek_cahaya2' => $request->reflek_cahaya2,
                'reflek_cornea' => $request->reflek_cornea,
                'reflek_cornea1' => $request->reflek_cornea1,
                'reflek_cornea2' => $request->reflek_cornea2,
                'nervus' => $request->nervus,
                'kaku_kuduk' => $request->kaku_kuduk,
                'sign' => $request->sign,
                'brudzinki' => $request->brudzinki,
                'kernig' => $request->kernig,
                'doll' => $request->doll,
                'phenomena' => $request->phenomena,
                'vertebra' => $request->vertebra,
                'extremitas' => $request->extremitas,
                'gerak1' => $request->gerak1,
                'gerak2' => $request->gerak2,
                'gerak3' => $request->gerak3,
                'gerak4' => $request->gerak4,
                'reflek_fisio1' => $request->reflek_fisio1,
                'reflek_fisio2' => $request->reflek_fisio2,
                'reflek_fisio3' => $request->reflek_fisio3,
                'reflek_fisio4' => $request->reflek_fisio4,
                'reflek_pato1' => $request->reflek_pato1,
                'reflek_pato2' => $request->reflek_pato2,
                'reflek_pato3' => $request->reflek_pato3,
                'reflek_pato4' => $request->reflek_pato4,
                'add_tambahan' => $request->add_tambahan,
                'clonus' => $request->clonus,
                'sensibilitas' => $request->sensibilitas,


            ]
        );
    }

    private function storeAsesmenGigi(Request $request)
    {
        // Check if a new image is provided
        if ($request->has('status_lokalis_image') && !empty($request->status_lokalis_image)) {
            $this->saveLokalisImage($request);
        }

        // Retain the existing image if no new image is provided
        $existingAsesmen = AsesmenGigi::where('visitation_id', $request->visitation_id)->first();
        $statusLokalis = $existingAsesmen->status_lokalis ?? null;

        AsesmenGigi::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'keluhan_utama' => $request->keluhan_utama,
                'autoanamnesis' => $request->autoanamnesis,
                'alloanamnesis' => $request->alloanamnesis,
                'anamnesis1' => $request->anamnesis1,
                'anamnesis2' => $request->anamnesis2,
                'keluhan_utama' => $request->keluhan_utama,
                'riwayat_penyakit_sekarang' => $request->riwayat_penyakit_sekarang,
                'allo_dengan' => $request->allo_dengan,
                'hasil_allo' => $request->hasil_allo,
                'riwayat_penyakit_dahulu' => $request->riwayat_penyakit_dahulu,
                'obat_dikonsumsi' => $request->obat_dikonsumsi,
                'keadaan_umum' => $request->keadaan_umum,
                'td' => $request->td,
                'n' => $request->n,
                'r' => $request->r,
                's' => $request->s,
                'e' => $request->e,
                'm' => $request->m,
                'v' => $request->v,
                'hsl' => $request->hsl,
                'kepala' => $request->kepala,
                'leher' => $request->leher,
                'thorax' => $request->thorax,
                'abdomen' => $request->abdomen,
                'genitalia' => $request->genitalia,
                'ext_atas' => $request->ext_atas,
                'ext_bawah' => $request->ext_bawah,
                'status_lokalis' => $request->status_lokalis ?? $existingAsesmen->status_lokalis ?? null,
                'ket_status_lokalis' => $request->ket_status_lokalis,

            ]
        );
    }



    private function storeAsesmenPenunjang(Request $request)
    {
        AsesmenPenunjang::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'diagnosakerja_1' => $request->diagnosakerja_1,
                'diagnosakerja_2' => $request->diagnosakerja_2,
                'diagnosakerja_3' => $request->diagnosakerja_3,
                'diagnosakerja_4' => $request->diagnosakerja_4,
                'diagnosakerja_5' => $request->diagnosakerja_5,
                'diagnosakerja_6' => $request->diagnosakerja_6,
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
    }

    private function saveLokalisImage(Request $request)
    {
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

            $filename = 'lokalis_' . $request->visitation_id . '_' . time() . '.' . $type;
            $storagePath = 'hasilasesmen/' . $filename;

            // Save to storage/app/public/hasilasesmen
            Storage::disk('public')->put($storagePath, $data);

            // Store the public path (accessible via /storage/hasilasesmen/filename)
            $request->merge([
                'status_lokalis' => "storage/{$storagePath}"
            ]);
        }
    }

    private function storeJenisKonsultasi(Request $request)
    {
        // Validasi
        $request->validate([
            'visitation_id' => 'required',
            'jenis_konsultasi' => 'required|exists:erm_konsultasi,id',
        ]);

        $visitationId = $request->visitation_id;
        $jenis_konsultasi = $request->jenis_konsultasi;

        // Ambil data jasa medis
        $jasa = Konsultasi::findOrFail($jenis_konsultasi);

        $existing = Billing::where('visitation_id', $visitationId)
            ->where('billable_id', $jasa->id)
            ->where('billable_type', Konsultasi::class)
            ->first();

        if (!$existing) {
            Billing::create([
                'visitation_id' => $visitationId,
                'billable_id' => $jasa->id,
                'billable_type' => Konsultasi::class,
                'jumlah' => $jasa->harga,
                'keterangan' => 'Tindakan: ' . $jasa->nama,
            ]);
        }
    }
}
