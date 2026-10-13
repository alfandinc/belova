<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Models\ERM\ScreeningVaksin;
use App\Models\ERM\Visitation;
use App\Models\User;

/**
 * Vaksin visit page (jenis_kunjungan 8). For now: the perawat's screening vaksin result.
 */
class VaksinController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::with(['pasien', 'dokter.user'])->findOrFail($visitationId);

        if ($blockers = $visitation->pendingServiceSteps()) {
            return view('erm.partials.service-blocked', array_merge(
                ['visitation' => $visitation, 'serviceLabel' => 'Vaksin', 'blockers' => $blockers],
                PasienHelperController::getDataPasien($visitationId),
                KunjunganHelperController::getCreateKunjungan($visitationId)
            ));
        }

        $screening = ScreeningVaksin::where('visitation_id', $visitation->id)->first();
        $screeningCreator = $screening && $screening->created_by
            ? User::whereKey($screening->created_by)->value('name')
            : null;

        return view('erm.vaksin.create', array_merge(
            ['visitation' => $visitation, 'screening' => $screening, 'screeningCreator' => $screeningCreator],
            PasienHelperController::getDataPasien($visitationId),
            KunjunganHelperController::getCreateKunjungan($visitationId)
        ));
    }
}
