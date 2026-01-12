<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ERM\IcPendaftaran;
use App\Models\ERM\Pasien;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class IcPendaftaranController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            // pasien id may be a string (non-incrementing PK) in this app — accept existing pasien ids
            'pasien_id' => 'nullable|exists:erm_pasiens,id',
            'signature' => 'required|string'
        ]);

        $pasien = null;
        if ($request->pasien_id) {
            $pasien = Pasien::find($request->pasien_id);
        }

        // signature is expected as data URL: data:image/png;base64,...
        $signatureData = $request->input('signature');
        if (preg_match('/^data:(.*);base64,(.*)$/', $signatureData, $matches)) {
            $mime = $matches[1];
            $data = base64_decode($matches[2]);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid signature data'], 422);
        }

        $now = now();
        $sigName = 'consents/signatures/' . Str::random(16) . '_' . $now->format('YmdHis') . '.png';
        Storage::disk('public')->put($sigName, $data);
        $signatureUrl = Storage::disk('public')->url($sigName);

        // We only store the signature image (no PDF generation)
        $record = IcPendaftaran::create([
            'pasien_id' => $request->pasien_id,
            'pdf_path' => null,
            'signature_path' => $sigName,
            'signed_at' => $now,
            'created_by' => auth()->id() ?? null,
        ]);

        return response()->json([
            'success' => true,
            'signature_url' => Storage::disk('public')->url($sigName),
            'record_id' => $record->id,
        ]);
    }

    /**
     * Batch check which pasien IDs have IC records.
     * Request: { ids: ["0004237", "004238", ...] }
     * Response: { mappings: { "0004237": { record_id: 123 }, ... } }
     */
    public function check(Request $request)
    {
        $ids = array_map('strval', (array) $request->input('ids', []));
        if (!$ids) return response()->json(['mappings' => []]);

        $mappings = [];
        foreach ($ids as $id) {
            $trim = ltrim($id, '0');
            $latest = IcPendaftaran::where('pasien_id', $id)
                ->orWhere('pasien_id', $trim)
                ->orderByDesc('signed_at')
                ->first();
            if ($latest) {
                $mappings[$id] = [ 'record_id' => $latest->id ];
            }
        }

        return response()->json(['mappings' => $mappings]);
    }

    /**
     * Render the IC PDF for a given pasien id.
     */
    public function pdf($pasienId)
    {
        $trim = ltrim((string)$pasienId, '0');
        $record = IcPendaftaran::where('pasien_id', $pasienId)
            ->orWhere('pasien_id', $trim)
            ->orderByDesc('signed_at')
            ->first();
        if (!$record) {
            return Response::make('IC Pendaftaran not found for this patient.', 404);
        }

        $pasien = Pasien::find($pasienId) ?? Pasien::find($trim);
        $signaturePath = Storage::disk('public')->path($record->signature_path);
        $signatureDataUri = null;
        if (is_file($signaturePath)) {
            $mime = 'image/png';
            $signatureDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($signaturePath));
        }

        $pdf = Pdf::loadView('erm.ic_pendaftaran.pdf', [
            'pasien' => $pasien,
            'record' => $record,
            'signatureDataUri' => $signatureDataUri,
        ])->setPaper('A4');

        $fname = 'IC_Pendaftaran_' . ($pasien ? $pasien->id : $pasienId) . '.pdf';
        return $pdf->stream($fname);
    }
}
