<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Examined patients (status_kunjungan 2) in the period: who they are (age / gender / status) and how
 * much they spend (paid invoices of those visits) per patient, per visit and per age group.
 * New vs returning lives in the Pasien Baru and Retensi widgets. Optional clinic filter.
 */
class PasienWidget implements Widget
{
    private const AGE_BUCKETS = [
        ['< 18', 0, 17],
        ['18–24', 18, 24],
        ['25–34', 25, 34],
        ['35–44', 35, 44],
        ['45–54', 45, 54],
        ['55+', 55, null],
    ];

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $klinikId = (int) request()->input('pasien_klinik_id', 0);

        $summary = DB::table('erm_visitations as v')
            ->join('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('finance_invoices as inv', function ($join) {
                $join->on('inv.visitation_id', '=', 'v.id')
                    ->whereNotNull('inv.payment_date')
                    ->whereIn('inv.status', ['paid', 'partial']);
            })
            ->where('v.status_kunjungan', 2)
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId))
            ->whereBetween('v.tanggal_visitation', [$start->toDateString(), $end->toDateString()])
            ->groupBy('v.pasien_id', 'p.tanggal_lahir', 'p.gender', 'p.status_pasien')
            ->selectRaw('v.pasien_id, p.tanggal_lahir, p.gender, p.status_pasien,
                COUNT(DISTINCT v.id) as visits, COALESCE(SUM(inv.total_amount), 0) as revenue')
            ->get();

        $patients = $summary->count();
        $revenue = (float) $summary->sum('revenue');

        $ages = $summary->map(function ($row) use ($end) {
            try {
                // Carbon 3 returns fractional years; whole years keep 24.9 inside "18–24"
                return $row->tanggal_lahir ? (int) floor(Carbon::parse($row->tanggal_lahir)->diffInYears($end)) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        $ageBars = collect(self::AGE_BUCKETS)->map(function ($bucket) use ($ages, $patients, $summary) {
            [$label, $min, $max] = $bucket;
            $keys = $ages->filter(fn ($age) => $age !== null && $age >= $min && ($max === null || $age <= $max))->keys();
            $count = $keys->count();

            return [
                'label' => $label,
                'count' => $count,
                'pct' => Fmt::share($count, $patients),
                'spend' => $count > 0 ? $summary->only($keys->all())->sum('revenue') / $count : 0,
            ];
        });
        $unknownAge = $ages->filter(fn ($age) => $age === null)->count();

        $gender = $summary->countBy(fn ($row) => match (strtolower(trim((string) $row->gender))) {
            'l', 'male', 'laki-laki', 'lakilaki' => 'Laki-laki',
            'p', 'female', 'perempuan' => 'Perempuan',
            default => 'Tidak diketahui',
        });

        $statuses = $summary
            ->countBy(fn ($row) => trim((string) $row->status_pasien) ?: 'Regular')
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $label) => ['label' => $label, 'count' => $count, 'pct' => Fmt::share($count, $patients)])
            ->values();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'klinikId' => $klinikId,
            'kliniks' => DB::table('erm_klinik')->orderBy('nama')->get(['id', 'nama']),
            'patients' => $patients,
            'visits' => (int) $summary->sum('visits'),
            'revenue' => $revenue,
            'revenuePerPatient' => $patients > 0 ? $revenue / $patients : 0,
            'revenuePerVisit' => $summary->sum('visits') > 0 ? $revenue / $summary->sum('visits') : 0,
            'averageAge' => $ages->filter(fn ($a) => $a !== null)->avg(),
            'ageBars' => $ageBars->all(),
            'unknownAge' => $unknownAge,
            'gender' => [
                ['label' => 'Perempuan', 'count' => (int) ($gender['Perempuan'] ?? 0), 'color' => Fmt::SERIES[0]],
                ['label' => 'Laki-laki', 'count' => (int) ($gender['Laki-laki'] ?? 0), 'color' => Fmt::SERIES[1]],
                ['label' => 'Tidak diketahui', 'count' => (int) ($gender['Tidak diketahui'] ?? 0), 'color' => Fmt::OTHER],
            ],
            'statuses' => $statuses->all(),
        ];
    }
}
