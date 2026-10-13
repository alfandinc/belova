<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Models\ERM\Visitation;

/**
 * MCU (medical check up) visit page (jenis_kunjungan 9). Content not built yet.
 */
class McuController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::with(['pasien', 'dokter.user'])->findOrFail($visitationId);
        $blockers = $visitation->pendingServiceSteps();

        return view($blockers ? 'erm.partials.service-blocked' : 'erm.mcu.create', array_merge(
            ['visitation' => $visitation, 'serviceLabel' => 'MCU', 'blockers' => $blockers],
            PasienHelperController::getDataPasien($visitationId),
            KunjunganHelperController::getCreateKunjungan($visitationId)
        ));
    }
}
