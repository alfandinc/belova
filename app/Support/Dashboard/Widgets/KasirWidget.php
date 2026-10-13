<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** The cashier's day: payments received today by method, invoices not paid yet, and open receivables. */
class KasirWidget implements Widget
{
    private const LIST_LIMIT = 6;

    private const METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'piutang' => 'Piutang',
    ];

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $paid = fn (Carbon $day) => DB::table('finance_invoices')
            ->whereNotNull('payment_date')
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('payment_date', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);

        $todayTotals = $paid($today)->selectRaw('COALESCE(SUM(total_amount), 0) as total, COUNT(*) as invoices')->first();
        $yesterdayTotal = (float) $paid($yesterday)->sum('total_amount');

        $methods = $paid($today)
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'lainnya') as method, SUM(total_amount) as total, COUNT(*) as invoices")
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($m) => [
                'label' => self::METHODS[$m->method] ?? ucwords(str_replace('_', ' ', $m->method)),
                'total' => (float) $m->total,
                'invoices' => (int) $m->invoices,
            ]);

        // Rp 0 invoices (fully discounted / empty) have nothing to collect
        $unpaidQuery = DB::table('finance_invoices as fi')->where('fi.status', 'issued')->where('fi.total_amount', '>', 0);
        $unpaid = (clone $unpaidQuery)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->orderByDesc('fi.created_at')
            ->limit(self::LIST_LIMIT)
            ->get(['fi.invoice_number', 'fi.total_amount', 'fi.created_at', 'p.nama as pasien']);

        $piutang = DB::table('finance_piutangs')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(amount - COALESCE(paid_amount, 0)), 0) as sisa')
            ->first();

        return [
            'todayLabel' => $today->translatedFormat('l, d M Y'),
            'total' => (float) $todayTotals->total,
            'invoices' => (int) $todayTotals->invoices,
            'trend' => Fmt::trend((float) $todayTotals->total, $yesterdayTotal),
            'methods' => $methods->all(),
            'unpaidCount' => (clone $unpaidQuery)->count(),
            'unpaidAmount' => (float) (clone $unpaidQuery)->sum('fi.total_amount'),
            'unpaid' => $unpaid->all(),
            'piutangCount' => (int) $piutang->total,
            'piutangSisa' => (float) $piutang->sisa,
        ];
    }
}
