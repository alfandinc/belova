<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Alergi;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Visitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlergiController extends Controller
{
    public function store(Request $request, string $visitation_id)
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
                        'verifikator_id' => Auth::id(), // Logged-in user ID as verifier
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
                    'verifikator_id' => Auth::id(),
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

    /**
     * Current allergies of the visit's patient, polled by the allergy pane of erm.partials.card-identitaspasien
     * so a change made on another screen shows up. Same shape as store()'s data.
     */
    public function current(string $visitation_id)
    {
        $pasienId = Visitation::whereKey($visitation_id)->value('pasien_id');
        abort_if(!$pasienId, 404);

        $alergiPasien = Alergi::where('pasien_id', $pasienId)->with('zataktif:id,nama')->get();
        $first = $alergiPasien->first();
        $kataKunci = $first?->katakunci;
        // Only a kata kunci, no zat aktif: the card shows it as 'belum diverifikasi' (names = [kata kunci])
        $unverified = $alergiPasien->count() === 1 && empty($first->zataktif_id);

        return response()->json([
            'status' => $first?->status,
            'kataKunci' => $kataKunci,
            'alergiNames' => $unverified
                ? [$kataKunci]
                : $alergiPasien->pluck('zataktif.nama')->filter()->values()->all(),
            'alergiIds' => $alergiPasien->pluck('zataktif_id')->filter()->values()->all(),
        ]);
    }
}
