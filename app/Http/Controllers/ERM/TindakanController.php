<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Tindakan;
use App\Models\ERM\PaketTindakan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\InformConsent;

class TindakanController extends Controller
{
    public function create($visitationId)
    {
        // Kosongkan dulu, tidak ada data dummy
        $visitation = Visitation::findOrFail($visitationId);
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);
        $spesialisasiId = $visitation->dokter->spesialisasi_id;


        // dd($spesialisasiId);
        return view('erm.tindakan.create', array_merge([
            'visitation' => $visitation,
            'spesialisasiId' => $spesialisasiId,
        ], $pasienData, $createKunjunganData));
    }

    public function getTindakanData(Request $request, $spesialisasiId)
    {
        $tindakan = Tindakan::where('spesialis_id', $spesialisasiId)->get();

        return datatables()->of($tindakan)
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-success btn-sm buat-tindakan" data-id="' . $row->id . '" data-type="tindakan">Buat</button>';
            })
            ->make(true);
    }

    public function getPaketTindakanData(Request $request, $spesialisasiId)
    {
        $paketTindakan = PaketTindakan::whereHas('tindakan', function ($query) use ($spesialisasiId) {
            $query->where('spesialis_id', $spesialisasiId);
        })->with('tindakan')->get();

        return datatables()->of($paketTindakan)
            ->addColumn('action', function ($row) {
                // Properly encode tindakan data as JSON
                $tindakanJson = json_encode($row->tindakan);
                return '<button class="btn btn-success btn-sm buat-paket-tindakan" data-id="' . $row->id . '" data-tindakan=\'' . $tindakanJson . '\'>Buat</button>';
            })
            ->make(true);
    }

    public function informConsent($id)
    {
        $tindakan = Tindakan::findOrFail($id);
        $visitation = request()->query('visitation_id');
        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation);
        $pasien = $visitation->pasien;

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        if (View::exists("erm.tindakan.inform-consent.{$viewName}")) {
            return view("erm.tindakan.inform-consent.{$viewName}", compact('tindakan', 'pasien', 'visitation'));
        }
        return view('erm.tindakan.inform-consent.default', compact('tindakan', 'pasien', 'visitation'));
    }

    public function saveInformConsent(Request $request)
    {
        $data = $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'tindakan_id' => 'required|exists:erm_tindakan,id',
            'tanggal' => 'required|date',
            'signature' => 'required',
            'witness_signature' => 'required',
            'notes' => 'nullable|string',
            'nama_pasien' => 'required|string',
            'nama_saksi' => 'required|string',
        ]);

        $visitation = \App\Models\ERM\Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $tindakan = Tindakan::findOrFail($data['tindakan_id']);

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.{$viewName}", [
            'data' => $data,
            'pasien' => $pasien,
            'visitation' => $visitation,
            'tindakan' => $tindakan,
        ]);

        $pdfPath = 'inform-consent/' . $pasien->id . '-' . $visitation->id . '-' . $tindakan->id . '.pdf';
        Storage::put('public/' . $pdfPath, $pdf->output());
        InformConsent::create([
            'visitation_id' => $data['visitation_id'],
            'tindakan_id' => $data['tindakan_id'],
            'file_path' => $pdfPath,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
