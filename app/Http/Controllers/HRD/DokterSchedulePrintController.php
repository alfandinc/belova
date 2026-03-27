<?php

namespace App\Http\Controllers\HRD;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\DokterSchedule;
use App\Models\HRD\ShiftDokter;
use App\Models\ERM\Klinik;
use Mpdf\Mpdf;

class DokterSchedulePrintController extends Controller
{
    protected function defaultDoctorColor(int $dokterId): string
    {
        $palette = [
            '#FFD54F',
            '#FF8A65',
            '#FF7043',
            '#BA68C8',
            '#64B5F6',
            '#4DB6AC',
            '#FFB74D',
            '#E57373',
        ];

        return $palette[$dokterId % count($palette)];
    }

    protected function normalizeColor(?string $color, int $dokterId): string
    {
        $trimmed = strtoupper(trim((string) $color));
        if (preg_match('/^#[0-9A-F]{6}$/', $trimmed)) {
            return $trimmed;
        }

        return $this->defaultDoctorColor($dokterId);
    }

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
        $doctorColors = ShiftDokter::query()
            ->select('dokter_id', 'color_hex', 'id')
            ->orderByDesc('id')
            ->get()
            ->groupBy('dokter_id')
            ->map(function ($rows, $dokterId) {
                return $this->normalizeColor(optional($rows->first())->color_hex, (int) $dokterId);
            })
            ->toArray();
    Log::info('DokterSchedulePrintController: schedules count=' . $schedules->count());
        $clinic = $clinicId ? Klinik::find($clinicId) : null;
        $html = view('hrd.dokter_schedule.print', compact('schedules', 'month', 'clinic', 'doctorColors'))->render();
        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('jadwal-dokter.pdf', 'S'))->header('Content-Type', 'application/pdf');
    }
}
