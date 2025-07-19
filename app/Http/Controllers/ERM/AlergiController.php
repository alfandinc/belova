<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Alergi;
use App\Models\ERM\Pasien;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Visitation;
use Illuminate\Http\Request;

class AlergiController extends Controller
{
    public function store(Request $request, $visitation_id)
    {
        // Validation for zat aktif
        $request->validate([
            'zataktif_id' => 'nullable|array',
            'zataktif_id.*' => 'exists:erm_zataktif,id',
        ]);

        // Get visitation by ID and retrieve the pasien_id from the visitation
        $visitation = Visitation::findOrFail($visitation_id);
        $pasien_id = $visitation->pasien_id; // The ID of the related pasien

        // Get the existing allergies for this patient
        $existingAlergi = Alergi::where('pasien_id', $pasien_id)->get();


        // Get the new allergy IDs that were selected
        $newAlergiIds = $request->zataktif_id ?? [];

        // 1. Remove allergies that are no longer selected
        $removedAlergi = $existingAlergi->whereNotIn('zataktif_id', $newAlergiIds);
        foreach ($removedAlergi as $alergi) {
            $alergi->delete();
        }

        // 2. Add new selected allergies or update existing ones
        if (!empty($newAlergiIds)) {
            foreach ($newAlergiIds as $zatId) {
                Alergi::updateOrCreate(
                    [
                        'pasien_id' => $pasien_id,
                        'zataktif_id' => $zatId,
                    ],
                    [
                        'status' => $request->statusAlergi,
                        'katakunci' => $request->katakunci,
                        'verif_status' => $request->verifikasi ?? '1', // Default verification status
                        'verifikator_id' => auth()->id(), // Logged-in user ID as verifier
                    ]
                );
            }
        } else if ($request->statusAlergi === 'ada' && !empty($request->katakunci)) {
            // If no zat aktif selected but katakunci is filled, store a record with zataktif_id = null
            Alergi::updateOrCreate(
                [
                    'pasien_id' => $pasien_id,
                    'zataktif_id' => null,
                ],
                [
                    'status' => $request->statusAlergi,
                    'katakunci' => $request->katakunci,
                    'verif_status' => $request->verifikasi ?? '1',
                    'verifikator_id' => auth()->id(),
                ]
            );
        }

        // Get updated allergy names for the response
        $alergiNames = [];
        if ($request->statusAlergi === 'ada') {
            if (!empty($newAlergiIds)) {
                $alergiNames = ZatAktif::whereIn('id', $newAlergiIds)
                    ->pluck('nama')
                    ->toArray();
            } elseif (!empty($request->katakunci)) {
                $alergiNames = [$request->katakunci];
            }
        }

        // Return JSON response for Ajax
        return response()->json([
            'success' => true,
            'message' => 'Alergi berhasil disimpan.',
            'data' => [
                'status' => $request->statusAlergi,
                'kataKunci' => $request->katakunci ?? '-',
                'alergiNames' => $alergiNames
            ]
        ]);
    }
}
