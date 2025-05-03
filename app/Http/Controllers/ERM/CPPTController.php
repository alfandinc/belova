<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Carbon;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Alergi;

class CPPTController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);


        return view('erm.cppt.create', array_merge([
            'visitation' => $visitation,
        ], $pasienData, $createKunjunganData));
    }
}
