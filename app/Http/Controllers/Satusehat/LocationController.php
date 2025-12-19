<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Satusehat\Location;
use App\Models\ERM\Klinik;

class LocationController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::orderBy('nama')->get();
        return view('satusehat.locations.index', compact('kliniks'));
    }

    public function data()
    {
        $locations = Location::with('klinik')->orderBy('id','desc')->get();
        $rows = $locations->map(function($loc){
            return [
                'id' => $loc->id,
                'klinik' => optional($loc->klinik)->nama,
                'name' => $loc->name,
                'identifier_value' => $loc->identifier_value,
                'province' => $loc->province,
                'city' => $loc->city,
                'latlng' => ($loc->latitude || $loc->longitude) ? ($loc->latitude . ', ' . $loc->longitude) : '',
                'aksi' => '<button class="btn btn-sm btn-primary btn-edit" data-id="'.$loc->id.'">Edit</button> '
                         .'<button class="btn btn-sm btn-danger btn-delete" data-id="'.$loc->id.'">Delete</button>'
            ];
        });
        return response()->json(['data' => $rows]);
    }

    public function show(Location $location)
    {
        return response()->json(['ok' => true, 'data' => $location]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'klinik_id' => 'nullable|integer',
            'location_id' => 'nullable|string',
            'description' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'village' => 'nullable|string',
            'rt' => 'nullable|string',
            'rw' => 'nullable|string',
            'line' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'identifier_value' => 'nullable|string',
            'name' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);
        // normalize empty strings to null for numeric fields
        if(array_key_exists('latitude', $data) && $data['latitude'] === '') $data['latitude'] = null;
        if(array_key_exists('longitude', $data) && $data['longitude'] === '') $data['longitude'] = null;
        $loc = Location::create($data);
        return response()->json(['ok' => true, 'data' => $loc]);
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'klinik_id' => 'nullable|integer',
            'location_id' => 'nullable|string',
            'description' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'village' => 'nullable|string',
            'rt' => 'nullable|string',
            'rw' => 'nullable|string',
            'line' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'identifier_value' => 'nullable|string',
            'name' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);
        if(array_key_exists('latitude', $data) && $data['latitude'] === '') $data['latitude'] = null;
        if(array_key_exists('longitude', $data) && $data['longitude'] === '') $data['longitude'] = null;
        $location->update($data);
        return response()->json(['ok' => true, 'data' => $location]);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['ok' => true]);
    }
}
