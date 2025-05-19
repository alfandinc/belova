<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use App\Models\ERM\SuratIstirahat;
use App\Models\ERM\Visitation;
use PDF;

class SuratIstirahatController extends Controller
{
    public function index($pasien_id)
    {
        $visitation = Visitation::where('pasien_id', $pasien_id)->latest()->first();
        $visitId = $visitation->id;
        $pasien = Pasien::with('suratIstirahats')->findOrFail($pasien_id);
        $surats = $pasien->suratIstirahats;

        $pasienData = PasienHelperController::getDataPasien($visitId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitId);


        // dd($surats);
        // return view('erm.suratistirahat.index', compact('pasien', 'surats', 'visitation'));

        return view('erm.suratistirahat.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'surats' => $surats,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $jumlah_hari = now()->parse($request->tanggal_mulai)->diffInDays(now()->parse($request->tanggal_selesai)) + 1;

        $surat = SuratIstirahat::create([
            'pasien_id' => $request->pasien_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlah_hari,
        ]);

        return response()->json($surat->load('pasien'));
    }

    public function cetak($id)
    {
        $surat = SuratIstirahat::with('pasien')->findOrFail($id);
        $pdf = PDF::loadView('erm.surat_istirahat.pdf', compact('surat'));
        return $pdf->stream('surat-istirahat.pdf');
    }
}
