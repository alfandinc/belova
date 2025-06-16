<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Tindakan;
use App\Models\ERM\PaketTindakan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ERM\InformConsent;

use Carbon\Carbon;


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
                return '
                <button class="btn btn-success btn-sm buat-tindakan" data-id="' . $row->id . '" data-type="tindakan">Buat Tindakan</button>
                <a href="' . route('erm.tindakan.sop', $row->id) . '" class="btn btn-info btn-sm" target="_blank">SOP</a>
            ';
            })
            ->rawColumns(['action'])
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
        $visitation = Visitation::findOrFail($visitation);
        $pasien = $visitation->pasien;

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        if (View::exists("erm.tindakan.inform-consent.{$viewName}")) {
            return view("erm.tindakan.inform-consent.{$viewName}", compact('tindakan', 'pasien', 'visitation'));
        }
        return view('erm.tindakan.inform-consent.default', compact('tindakan', 'pasien', 'visitation'));
    }

    public function saveInformConsent(Request $request)
    {
        // Map field names from JavaScript to what controller expects
        if ($request->has('signatureData') && !$request->has('signature')) {
            $request->merge(['signature' => $request->signatureData]);
        }

        if ($request->has('witnessSignatureData') && !$request->has('witness_signature')) {
            $request->merge(['witness_signature' => $request->witnessSignatureData]);
        }
        $data = $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'tindakan_id' => 'required|exists:erm_tindakan,id',
            'tanggal' => 'required|date',
            'signature' => 'required',
            'witness_signature' => 'required',
            'notes' => 'nullable|string',
            'nama_pasien' => 'required|string',
            'nama_saksi' => 'required|string',
            'paket_id' => 'nullable|exists:erm_paket_tindakan,id',
        ]);

        $visitation = Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $tindakan = Tindakan::findOrFail($data['tindakan_id']);

        $viewName = strtolower(str_replace(' ', '_', $tindakan->nama));
        $pdf = PDF::loadView("erm.tindakan.inform-consent.pdf.{$viewName}", [
            'data' => $data,
            'pasien' => $pasien,
            'visitation' => $visitation,
            'tindakan' => $tindakan,
        ]);

        $timestamp = now()->format('YmdHis');
        $pdfPath = 'inform-consent/' . $pasien->id . '-' . $visitation->id . '-' . $tindakan->id . '-' . $timestamp . '.pdf';
        // Storage::put('public/' . $pdfPath, $pdf->output());
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $informConsent = InformConsent::create([
            'visitation_id' => $data['visitation_id'],
            'tindakan_id' => $data['tindakan_id'],
            'file_path' => $pdfPath,
            'paket_id' => $data['paket_id'] ?? null,
            'created_at' => now(),
        ]);
        $billing = null;

        if (isset($data['paket_id'])) {
            // This is a paket tindakan
            $paketId = $data['paket_id'];
            $visitationId = $data['visitation_id'];


            $existingBilling = Billing::where('visitation_id', $visitationId)
                ->where('billable_id', $paketId)
                ->where('billable_type', 'App\\Models\\ERM\\PaketTindakan')
                ->first();

            if (!$existingBilling) {

                $paketTindakan = PaketTindakan::find($paketId);

                $billingData = [
                    'visitation_id' => $visitationId,
                    'billable_id' => $paketId,
                    'billable_type' => 'App\\Models\\ERM\\PaketTindakan',
                    'jumlah' => !empty($data['jumlah']) ? $data['jumlah'] : $paketTindakan->harga_paket,
                    'keterangan' => !empty($data['keterangan']) ? $data['keterangan'] : 'Paket Tindakan: ' . $paketTindakan->nama
                ];

                $billing = Billing::create($billingData);
            } else {
                $billing = $existingBilling;
            }
        } else {
            // This is a single tindakan
            $billingData = [
                'visitation_id' => $data['visitation_id'],
                'billable_id' => $data['tindakan_id'],
                'billable_type' => 'App\\Models\\ERM\\Tindakan',
                'jumlah' => $tindakan->harga,
                'keterangan' => 'Tindakan: ' . $tindakan->nama
            ];

            // Override with request data if provided
            if (!empty($data['jumlah'])) {
                $billingData['jumlah'] = $data['jumlah'];
            }

            if (!empty($data['keterangan'])) {
                $billingData['keterangan'] = $data['keterangan'];
            }

            $billing = Billing::create($billingData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inform consent and billing created successfully',
            'informConsent' => $informConsent,
            'billing' => $billing
        ]);
    }

    public function getInformConsentHistory($visitationId)
    {
        // First, get the patient ID from the current visitation
        $currentVisitation = Visitation::findOrFail($visitationId);
        $patientId = $currentVisitation->pasien_id;

        // Get all visitations for this patient
        $patientVisitations = Visitation::where('pasien_id', $patientId)
            ->pluck('id')->toArray();

        // Get all inform consents for all visitations of this patient
        $history = InformConsent::whereIn('visitation_id', $patientVisitations)
            ->with(['tindakan', 'visitation'])
            ->get()
            ->map(function ($item) use ($visitationId) {
                $paketName = null;
                if ($item->paket_id) {
                    $paket = PaketTindakan::find($item->paket_id);
                    $paketName = $paket ? $paket->nama : null;
                }

                // Format the date using Carbon
                $tanggalFormatted = '-';
                if (isset($item->visitation->tanggal_visitation)) {
                    $tanggalFormatted = Carbon::parse($item->visitation->tanggal_visitation)
                        ->locale('id') // Set locale to Indonesian
                        ->isoFormat('D MMMM YYYY'); // Format: 4 Juni 2025
                }

                return [
                    'id' => $item->id, // Add this line to include the ID
                    'tanggal' => $tanggalFormatted,
                    'tindakan' => $item->tindakan->nama ?? '-',
                    'paket' => $paketName ?? '-',
                    'file_path' => $item->file_path,
                    'before_image_path' => $item->before_image_path,
                    'after_image_path' => $item->after_image_path,
                    'current' => ($item->visitation_id == $visitationId) ? true : false
                ];
            });

        return datatables()->of($history)
            ->addColumn('dokumen', function ($row) {
                if (empty($row['file_path'])) {
                    return '<span class="text-muted">Tidak ada dokumen</span>';
                }

                $url = Storage::url($row['file_path']);
                return '<a href="' . $url . '" target="_blank" class="btn btn-info btn-sm">Lihat</a>';
            })
            ->addColumn('status', function ($row) {
                return $row['current'] ?
                    '<span class="badge badge-success">Kunjungan Saat Ini</span>' :
                    '<span class="badge badge-secondary">Kunjungan Sebelumnya</span>';
            })
            ->addColumn('foto_hasil', function ($row) {
                return '<button class="btn btn-primary btn-sm foto-hasil-btn" ' .
                    'data-id="' . $row['id'] . '" ' .
                    'data-before="' . ($row['before_image_path'] ?? '') . '" ' .
                    'data-after="' . ($row['after_image_path'] ?? '') . '">' .
                    'Foto Hasil</button>';
            })
            ->rawColumns(['dokumen', 'status', 'foto_hasil'])
            ->make(true);
    }

    public function generateSopPdf($id)
    {
        $tindakan = Tindakan::with('sop')->findOrFail($id);

        // If SOP doesn't exist, return a message
        if ($tindakan->sop->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'SOP belum tersedia untuk tindakan ini'
            ]);
        }

        $pdf = PDF::loadView('erm.tindakan.sop.pdf', [
            'tindakan' => $tindakan,
            'sopList' => $tindakan->sop->sortBy('urutan')
        ]);

        $filename = 'SOP-' . str_replace(' ', '-', $tindakan->nama) . '.pdf';

        return $pdf->stream($filename);
    }

    public function uploadFoto(Request $request, $id)
    {
        $request->validate([
            'before_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'after_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $informConsent = InformConsent::findOrFail($id);

        if ($request->hasFile('before_image')) {
            // Delete existing image if it exists
            if ($informConsent->before_image_path && Storage::disk('public')->exists($informConsent->before_image_path)) {
                Storage::disk('public')->delete($informConsent->before_image_path);
            }

            // Store new image
            $beforePath = $request->file('before_image')->store('tindakan-images', 'public');
            $informConsent->before_image_path = $beforePath;
        }

        if ($request->hasFile('after_image')) {
            // Delete existing image if it exists
            if ($informConsent->after_image_path && Storage::disk('public')->exists($informConsent->after_image_path)) {
                Storage::disk('public')->delete($informConsent->after_image_path);
            }

            // Store new image
            $afterPath = $request->file('after_image')->store('tindakan-images', 'public');
            $informConsent->after_image_path = $afterPath;
        }

        $informConsent->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto hasil berhasil diupload',
            'before_path' => $informConsent->before_image_path ? Storage::url($informConsent->before_image_path) : null,
            'after_path' => $informConsent->after_image_path ? Storage::url($informConsent->after_image_path) : null,
        ]);
    }
}
