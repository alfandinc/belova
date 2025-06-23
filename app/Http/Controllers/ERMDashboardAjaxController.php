<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;

class ERMDashboardAjaxController extends Controller
{
    public function mostFrequentPatient(Request $request)
    {
        $dokterId = auth()->user()->dokter->id ?? null;
        $selectedYear = (int) $request->input('year', now()->year);
        $selectedMonth = (int) $request->input('month', now()->month);
        $result = [ 'name' => '-', 'count' => 0 ];
        if ($dokterId) {
            $mostFrequent = Visitation::selectRaw('pasien_id, COUNT(*) as count')
                ->where('dokter_id', $dokterId)
                ->whereYear('tanggal_visitation', $selectedYear)
                ->whereMonth('tanggal_visitation', $selectedMonth)
                ->groupBy('pasien_id')
                ->orderByDesc('count')
                ->first();
            if ($mostFrequent) {
                $pasien = Pasien::find($mostFrequent->pasien_id);
                $result['name'] = $pasien ? $pasien->nama : '-';
                $result['count'] = $mostFrequent->count;
            }
        }
        return response()->json($result);
    }
}
