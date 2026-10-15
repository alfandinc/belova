<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The cashier's day: payments received today by method, invoices not paid yet, and open receivables.
 * Received / unpaid amounts are also split per clinic (the invoice's visit clinic).
 */
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

        $paid = fn (Carbon $day) => DB::table('finance_invoices as fi')
            ->whereNotNull('fi.payment_date')
            ->whereIn('fi.status', ['paid', 'partial'])
            ->whereBetween('fi.payment_date', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);

        $todayTotals = $paid($today)->selectRaw('COALESCE(SUM(fi.total_amount), 0) as total, COUNT(*) as invoices')->first();
        $yesterdayTotal = (float) $paid($yesterday)->sum('fi.total_amount');

        $byMethod = $paid($today)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->selectRaw("COALESCE(NULLIF(fi.payment_method, ''), 'lainnya') as method, v.klinik_id, SUM(fi.total_amount) as total, COUNT(*) as invoices")
            ->groupBy('method', 'v.klinik_id')
            ->get();

        $methods = $byMethod->groupBy('method')
            ->map(fn ($rows, $method) => [
                'label' => self::METHODS[$method] ?? ucwords(str_replace('_', ' ', $method)),
                'total' => (float) $rows->sum('total'),
                'invoices' => (int) $rows->sum('invoices'),
                'clinics' => Clinics::split($rows->mapWithKeys(fn ($r) => [(string) $r->klinik_id => $r->total]), fn ($v) => Fmt::rpShort($v)),
            ])
            ->sortByDesc('total')
            ->values();

        $todayByKlinik = $byMethod->groupBy(fn ($r) => (string) $r->klinik_id)->map(fn ($rows) => $rows->sum('total'));

        // Rp 0 invoices (fully discounted / empty) have nothing to collect
        $unpaidQuery = DB::table('finance_invoices as fi')->where('fi.status', 'issued')->where('fi.total_amount', '>', 0);
        $unpaid = (clone $unpaidQuery)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->orderByDesc('fi.created_at')
            ->limit(self::LIST_LIMIT)
            ->get(['fi.invoice_number', 'fi.total_amount', 'fi.created_at', 'p.nama as pasien', 'v.klinik_id']);

        $unpaidByKlinik = (clone $unpaidQuery)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COUNT(*) as total')
            ->pluck('total', 'klinik_id');

        $piutang = DB::table('finance_piutangs')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(amount - COALESCE(paid_amount, 0)), 0) as sisa')
            ->first();

        return [
            'todayLabel' => $today->translatedFormat('l, d M Y'),
            'total' => (float) $todayTotals->total,
            'invoices' => (int) $todayTotals->invoices,
            'trend' => Fmt::trend((float) $todayTotals->total, $yesterdayTotal),
            'totalClinics' => Clinics::split($todayByKlinik, fn ($v) => Fmt::rp($v)),
            'methods' => $methods->all(),
            'unpaidCount' => (clone $unpaidQuery)->count(),
            'unpaidAmount' => (float) (clone $unpaidQuery)->sum('fi.total_amount'),
            'unpaidClinics' => Clinics::split($unpaidByKlinik),
            'unpaid' => $unpaid->all(),
            'piutangCount' => (int) $piutang->total,
            'piutangSisa' => (float) $piutang->sisa,
        ];
    }
}
