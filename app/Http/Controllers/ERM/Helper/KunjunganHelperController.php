<?php

namespace App\Http\Controllers\ERM\Helper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\Klinik;
use App\Models\ERM\Visitation;

class KunjunganHelperController extends Controller
{
    public static function getCreateKunjungan($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $pasienName = $visitation->pasien->nama;
        $metodeBayar = MetodeBayar::all(); // ambil semua data metode bayar
        $dokters = Dokter::with('spesialisasi')->get(); // ambil semua dokter
        $kliniks = Klinik::all();

        // dd($pasienName, $metodeBayar, $dokters);


        return [
            'dokters' => $dokters,
            'metodeBayar' => $metodeBayar,
            'pasienName' => $pasienName,
            'kliniks' => $kliniks,
        ];
    }
}
