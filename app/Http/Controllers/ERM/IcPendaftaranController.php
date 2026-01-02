<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ERM\IcPendaftaran;
use App\Models\ERM\Pasien;

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
}
