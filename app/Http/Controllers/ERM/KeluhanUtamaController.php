<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\KeluhanUtama;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;

class KeluhanUtamaController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->query('search');
        $visitationId = $request->query('visitation_id');

        // Debug visitation_id
        if (!$visitationId) {
            return response()->json(['error' => 'Visitation ID is required'], 400);
        }

        // Retrieve spesialis_id from the Visitation model
        $visitation = Visitation::where('id', $visitationId)
            ->with('dokter')
            ->first();

        if (!$visitation || !$visitation->dokter) {
            return response()->json(['error' => 'Dokter or Visitation not found'], 404);
        }

        $spesialisId = $visitation->dokter->spesialisasi_id;

        if (!$spesialisId) {
            return response()->json(['error' => 'Spesialis ID not found'], 404);
        }

        // Query Keluhan Utama based on spesialis_id
        $query = KeluhanUtama::where('spesialisasi_id', $spesialisId);

        // dd($query);

        if ($search) {
            $query->where('keluhan', 'like', '%' . $search . '%');
        }

        return response()->json($query->get());
    }
}
