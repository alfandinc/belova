<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;

/**
 * Where the period's omset comes from (invoice items): category split with growth vs the previous
 * period of the same length, every category split per clinic, best-selling tindakan / products (with
 * their clinic split and a "Lihat semua" list), and payment methods.
 */
class SumberOmsetWidget implements Widget
{
    private const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'piutang' => 'Piutang',
        'lainnya' => 'Lainnya',
    ];

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $current = Revenue::byCategory($start, $end);
        $previous = Revenue::byCategory($prevStart, $prevEnd);
        $total = (float) $current->sum();

        // klinik_id => category => omset
        $perKlinik = Clinics::all()->map(fn ($k) => Revenue::byCategory($start, $end, $k->id));

        $categories = collect(Revenue::CATEGORIES)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'amount' => $current[$key],
            'pct' => Fmt::share($current[$key], $total),
            'trend' => Fmt::trend($current[$key], $previous[$key]),
            'color' => Revenue::CATEGORY_COLORS[$key],
            'clinics' => Clinics::split(
                $perKlinik->map(fn ($split) => $split[$key])->put('', max(0, $current[$key] - $perKlinik->sum(fn ($split) => $split[$key]))),
                fn ($v) => Fmt::rpShort($v)
            ),
        ])->values();

        $clinics = Clinics::all()
            ->map(function ($klinik) use ($perKlinik) {
                $split = $perKlinik[$klinik->id];
                $sum = (float) $split->sum();

                return [
                    'nama' => $klinik->short,
                    'color' => $klinik->color,
                    'total' => $sum,
                    'segments' => collect(Revenue::CATEGORIES)->map(fn ($label, $key) => [
                        'label' => $label,
                        'value' => Fmt::rp($split[$key]),
                        'pct' => Fmt::share($split[$key], $sum),
                        'color' => Revenue::CATEGORY_COLORS[$key],
                    ])->values()->all(),
                ];
            })
            ->filter(fn ($c) => $c['total'] > 0)
            ->values();

        $methods = Revenue::between($start, $end)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->selectRaw("COALESCE(NULLIF(fi.payment_method, ''), 'lainnya') as method, v.klinik_id, SUM(fi.total_amount) as total, COUNT(*) as invoices")
            ->groupBy('method', 'v.klinik_id')
            ->get()
            ->groupBy('method')
            ->map(fn ($rows, $method) => [
                'label' => self::PAYMENT_METHODS[$method] ?? ucwords(str_replace('_', ' ', $method)),
                'total' => (float) $rows->sum('total'),
                'pct' => Fmt::share($rows->sum('total'), $total),
                'invoices' => (int) $rows->sum('invoices'),
                'clinics' => Clinics::split($rows->mapWithKeys(fn ($r) => [(string) $r->klinik_id => $r->total]), fn ($v) => Fmt::rpShort($v)),
            ])
            ->sortByDesc('total')
            ->values();

        $top = fn (string $category) => Revenue::items($start, $end, $category, 5)
            ->each(fn ($item) => $item->clinics = Clinics::split($item->byKlinik, fn ($v) => Fmt::rpShort($v)))
            ->all();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'total' => $total,
            'categories' => $categories->all(),
            'clinics' => $clinics->all(),
            'topTindakan' => $top('tindakan'),
            'topObat' => $top('obat'),
            'methods' => $methods->all(),
            'itemsUrl' => route('dashboard.omset-items', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]),
        ];
    }
}
