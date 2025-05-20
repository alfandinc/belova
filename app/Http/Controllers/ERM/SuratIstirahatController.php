<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use App\Models\ERM\SuratIstirahat;
use App\Models\ERM\Visitation;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\Dokter;
use SimpleSoftwareIO\QrCode\Generator;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\GdImageBackEnd;

class SuratIstirahatController extends Controller
{
    public function index($pasien_id)
    {
        $visitation = Visitation::where('pasien_id', $pasien_id)->latest()->first();
        $visitId = $visitation->id;


        $pasien = Pasien::with('suratIstirahats')->findOrFail($pasien_id);
        $surats = $pasien->suratIstirahats()->with('dokter.user', 'dokter.spesialisasi')->get();

        $pasienData = PasienHelperController::getDataPasien($visitId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);


        $dokters = Dokter::with('user')->get();
        $dokterUserId = auth()->id(); // Get the ID of the logged-in user

        // dd($surats);

        // dd($dokters);

        return view('erm.suratistirahat.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'surats' => $surats,
            'dokters' => $dokters,
            'dokterUserId' => $dokterUserId,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $jumlah_hari = now()->parse($request->tanggal_mulai)->diffInDays(now()->parse($request->tanggal_selesai)) + 1;

        $surat = SuratIstirahat::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlah_hari,
        ]);


        return response()->json($surat->load('pasien', 'dokter.user', 'dokter.spesialisasi'));
    }

    public function cetak($id)
    {
        $surat = SuratIstirahat::with(['dokter.user', 'pasien'])->findOrFail($id);
        return PDF::loadView('erm.suratistirahat.cetak', compact('surat'))->stream();
    }
}
