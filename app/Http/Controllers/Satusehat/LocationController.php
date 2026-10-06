<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Satusehat\Location;

// Locations are edited per klinik in Admin > Klinik Setting (tab SatuSehat); this controller keeps link/delete
class LocationController extends Controller
{
    // Attach a location that has no klinik (or is a second location for a klinik) to a klinik without one
    public function link(Request $request, Location $location)
    {
        $data = $request->validate(['klinik_id' => 'required|exists:erm_klinik,id']);
        if (Location::where('klinik_id', $data['klinik_id'])->where('id', '!=', $location->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Klinik ini sudah memiliki lokasi SatuSehat'], 422);
        }
        $location->update(['klinik_id' => $data['klinik_id']]);

        return response()->json(['ok' => true, 'message' => 'Lokasi dihubungkan ke klinik']);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['ok' => true]);
    }
}
