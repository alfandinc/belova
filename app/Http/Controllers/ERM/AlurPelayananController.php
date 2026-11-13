<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Services\ERM\AlurPelayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tahap Pelayanan strip / badges / timeline shared by Rawat Jalan, Billing and E-Resep.
 */
class AlurPelayananController extends Controller
{
    /** Strip counts for the page filters + the stage of the table rows shown (ids[]). */
    public function summary(Request $request)
    {
        $filters = $this->filters($request);

        return response()->json([
            'counts' => AlurPelayanan::counts($filters),
            'late' => AlurPelayanan::lateCounts($filters),
            'rows' => AlurPelayanan::forVisits((array) $request->input('ids', [])),
        ]);
    }

    /** Visits in one stage, longest waiting first (strip click). */
    public function list(Request $request)
    {
        $stage = (string) $request->input('tahap');
        abort_unless(array_key_exists($stage, AlurPelayanan::STAGES), 422, 'Tahap tidak dikenal');

        return response()->json(['data' => AlurPelayanan::listStage($this->filters($request), $stage)]);
    }

    public function timeline(string $visitationId)
    {
        $timeline = AlurPelayanan::timeline($visitationId);
        abort_unless($timeline, 404);

        return response()->json($timeline);
    }

    private function filters(Request $request): array
    {
        $filters = $request->only(['start_date', 'end_date', 'klinik_id', 'dokter_id', 'metode_group']);
        if (!in_array($filters['metode_group'] ?? null, ['umum', 'asuransi'], true)) {
            $filters['metode_group'] = null;
        }
        foreach (['start_date', 'end_date'] as $key) {
            if (!empty($filters[$key]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters[$key])) {
                $filters[$key] = null;
            }
        }
        $filters['dokter_id'] = ($filters['dokter_id'] ?? null)
            ?: AlurPelayanan::defaultDokterId(Auth::user(), $request->has('dokter_id'));

        return $filters;
    }
}
