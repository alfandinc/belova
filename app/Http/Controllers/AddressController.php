<?php

namespace App\Http\Controllers;

use App\Models\Area\District;
use App\Models\Area\Province;
use App\Models\Area\Regency;
use App\Models\Area\Village;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function getProvinces()
    {
        $data = Province::orderBy('name')->get();
        return response()->json($data);
    }

    public function getRegencies($province_id)
    {
        $data = Regency::where('province_id', $province_id)->get();
        return response()->json($data);
    }

    public function getDistricts($regency_id)
    {
        $data = District::where('regency_id', $regency_id)->get();
        return response()->json($data);
    }

    public function getVillages($district_id)
    {
        $data = Village::where('district_id', $district_id)->get();
        return response()->json($data);
    }

    /**
     * Select2 search for villages, optionally limited to one district.
     * Each result carries its full district -> regency -> province chain.
     */
    public function searchVillages(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $districtId = $request->input('district_id');

        // Searching across the whole country needs a few characters to stay fast
        if (!$districtId && mb_strlen($term) < 3) {
            return response()->json(['results' => []]);
        }

        $villages = Village::with('district.regency.province')
            ->when($districtId, fn ($query) => $query->where('district_id', $districtId))
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%' . $term . '%'))
            ->orderBy('name')
            ->limit(50)
            ->get();

        $results = $villages->map(function ($village) {
            $district = $village->district;
            $regency = $district?->regency;
            $province = $regency?->province;

            return [
                'id' => $village->id,
                'text' => implode(', ', array_filter([
                    $village->name,
                    $district?->name,
                    $regency?->name,
                    $province?->name,
                ])),
                'village' => $village,
            ];
        });

        return response()->json(['results' => $results]);
    }
}
