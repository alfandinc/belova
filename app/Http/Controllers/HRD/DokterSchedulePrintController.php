<?php

namespace App\Http\Controllers\HRD;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\DokterSchedule;
use App\Models\ERM\Klinik;
use Mpdf\Mpdf;

class DokterSchedulePrintController extends Controller
{
    public function print(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $clinicId = $request->input('clinic_id');
    Log::info('DokterSchedulePrintController: clinicId=' . $clinicId . ', month=' . $month);
        $query = DokterSchedule::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->with(['dokter.user']);
        if ($clinicId) {
            $query->whereHas('dokter', function($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        $schedules = $query->get();
    Log::info('DokterSchedulePrintController: schedules count=' . $schedules->count());
        $clinic = $clinicId ? Klinik::find($clinicId) : null;
        $html = view('hrd.dokter_schedule.print', compact('schedules', 'month', 'clinic'))->render();
        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('jadwal-dokter.pdf', 'S'))->header('Content-Type', 'application/pdf');
    }
}
