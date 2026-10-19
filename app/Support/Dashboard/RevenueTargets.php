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

    /**
     * What is still needed to reach a target whose months run from $start to $end (month bounds are
     * taken from them): the shortfall, and that shortfall spread over the calendar days left from today
     * (today included, as its omset is still coming in). Null without a target.
     *
     * @return array{target: float, need: float, over: float, reached: bool, daysLeft: int, perDay: ?float, ended: bool}|null
     */
    public static function gap(float $actual, float $target, Carbon $start, Carbon $end): ?array
    {
        if ($target <= 0) {
            return null;
        }

        $daysLeft = self::daysLeft($start, $end);
        $need = max(0.0, $target - $actual);

        return [
            'target' => $target,
            'need' => $need,
            'over' => max(0.0, $actual - $target),
            'reached' => $actual >= $target,
            'daysLeft' => $daysLeft,
            'perDay' => $need > 0 && $daysLeft > 0 ? $need / $daysLeft : null,
            'ended' => $daysLeft === 0,
        ];
    }

    /** Calendar days from today (included) to the end of $end's month; a future period counts from its first month. */
    public static function daysLeft(Carbon $start, Carbon $end): int
    {
        $first = $start->copy()->startOfMonth()->max(Carbon::today());
        $last = $end->copy()->endOfMonth()->startOfDay();

        return $last->lt($first) ? 0 : (int) $first->diffInDays($last) + 1;
    }
}
