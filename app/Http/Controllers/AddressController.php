<?php

namespace App\Http\Controllers;

use App\Models\Area\District;
use App\Models\Area\Province;
use App\Models\Area\Regency;
use App\Models\Area\Village;

class AddressController extends Controller
{
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
}
