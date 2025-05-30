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
use App\Models\ERM\AsesmenUmum;
use App\Models\ERM\JasaMedis;
use Illuminate\Support\Str;
use App\Models\ERM\Transaksi;


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
        ];
        $currentAsesmen = $asesmen[$spesialisasi] ?? null;

        // Lokalis image logic
        $lokalisDefaults = [
            'penyakit_dalam' => 'img/asesmen/dalam.jpeg',
        ];
        $lokalisPath = old('status_lokalis', $currentAsesmen->status_lokalis ?? null);
        $lokalisBackground = $lokalisPath ?: ($lokalisDefaults[$spesialisasi] ?? 'img/lokalis/default.png');

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        $jenisKonsultasi = JasaMedis::where('jenis', 'konsultasi')
            ->get();

        return view('erm.asesmendokter.create', array_merge([
            'visitation' => $visitation,
            'dataperawat' => $dataperawat,
            'asesmenPenunjang' => $asesmenPenunjang,
            'currentAsesmen' => $currentAsesmen,
            'spesialisasi' => $spesialisasi,
            'jenisKonsultasi' => $jenisKonsultasi,
            'lokalisPath' => $lokalisPath,
            'lokalisBackground' => $lokalisBackground,
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

            $filename = 'lokalis_' . $request->visitation_id . '.' . $type;
            $path = public_path("img/hasilasesmen/" . $filename);

            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $data);

            $request->merge([
                'status_lokalis' => "img/hasilasesmen/{$filename}"
            ]);
        }
    }

    private function storeJenisKonsultasi(Request $request)
    {
        // Validasi
        $request->validate([
            'visitation_id' => 'required',
            'jenis_konsultasi' => 'required|exists:erm_jasamedis,id',
        ]);

        $visitationId = $request->visitation_id;
        $jenis_konsultasi = $request->jenis_konsultasi;

        // Ambil data jasa medis
        $jasa = JasaMedis::findOrFail($jenis_konsultasi);

        // Cek apakah sudah ada transaksi yang sama untuk visitation ini dan jasa tersebut
        $existing = Transaksi::where('visitation_id', $visitationId)
            ->where('transaksible_id', $jasa->id)
            ->where('transaksible_type', JasaMedis::class)
            ->first();

        if (!$existing) {
            Transaksi::create([
                'visitation_id' => $visitationId,
                'transaksible_id' => $jasa->id,
                'transaksible_type' => JasaMedis::class,
                'jumlah' => $jasa->harga,
                'keterangan' => 'Tindakan: ' . $jasa->nama,
            ]);
        }
    }
}
