<?php

namespace App\Support\Dashboard;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/** Monthly revenue targets (finance_revenue_target) summed over every month a period touches. */
class RevenueTargets
{
    /** @return array{total: float, byKlinik: \Illuminate\Support\Collection, label: string} */
    public static function forPeriod(Carbon $start, Carbon $end): array
    {
        $months = collect(CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth()))
            ->map(fn (Carbon $m) => [$m->year, $m->month]);

        $rows = DB::table('finance_revenue_target')
            ->where(function ($q) use ($months) {
                foreach ($months as [$year, $month]) {
                    $q->orWhere(fn ($m) => $m->where('periode_tahun', $year)->where('periode_bulan', $month));
                }
            })
            ->groupBy('klinik_id')
            ->selectRaw('klinik_id, SUM(target_amount) as total')
            ->pluck('total', 'klinik_id')
            ->map(fn ($v) => (float) $v);

        $first = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();
        $label = $first->equalTo($last)
            ? $first->translatedFormat('F Y')
            : $first->translatedFormat($first->year === $last->year ? 'M' : 'M Y') . ' – ' . $last->translatedFormat('M Y');

        return ['total' => (float) $rows->sum(), 'byKlinik' => $rows, 'label' => $label];
    }
}
