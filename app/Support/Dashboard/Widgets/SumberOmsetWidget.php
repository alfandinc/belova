<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use Illuminate\Support\Facades\DB;

/**
 * Where the period's omset comes from (invoice items): category split with growth vs the previous
 * period of the same length, the split per clinic, best-selling tindakan / products, and payment methods.
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

        $categories = collect(Revenue::CATEGORIES)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'amount' => $current[$key],
            'pct' => Fmt::share($current[$key], $total),
            'trend' => Fmt::trend($current[$key], $previous[$key]),
            'color' => Revenue::CATEGORY_COLORS[$key],
        ])->values();

        $clinics = DB::table('erm_klinik')->orderBy('nama')->get(['id', 'nama'])
            ->map(function ($klinik) use ($start, $end) {
                $split = Revenue::byCategory($start, $end, (int) $klinik->id);
                $sum = (float) $split->sum();

                return [
                    'nama' => $klinik->nama,
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
            ->sortByDesc('total')
            ->values();

        $methods = Revenue::between($start, $end)
            ->selectRaw("COALESCE(NULLIF(fi.payment_method, ''), 'lainnya') as method, SUM(fi.total_amount) as total, COUNT(*) as invoices")
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($m) => [
                'label' => self::PAYMENT_METHODS[$m->method] ?? ucwords(str_replace('_', ' ', $m->method)),
                'total' => (float) $m->total,
                'pct' => Fmt::share($m->total, $total),
                'invoices' => (int) $m->invoices,
            ]);

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'total' => $total,
            'categories' => $categories->all(),
            'clinics' => $clinics->all(),
            'topTindakan' => Revenue::topItems($start, $end, 'tindakan')->all(),
            'topObat' => Revenue::topItems($start, $end, 'obat')->all(),
            'methods' => $methods->all(),
        ];
    }
}
