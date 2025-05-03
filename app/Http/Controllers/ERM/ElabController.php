<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;

class ElabController extends Controller
{
    public function create($visitationId)
    {
        // Kosongkan dulu, tidak ada data dummy
        $visitation = Visitation::findOrFail($visitationId);

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.elab.create', array_merge([
            'visitation' => $visitation,
        ], $pasienData, $createKunjunganData));
    }
}
