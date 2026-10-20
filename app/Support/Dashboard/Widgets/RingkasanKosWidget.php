<?php

namespace App\Support\Dashboard\Widgets;

use App\Http\Controllers\BCL\InventoriesController;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Rooms;
use App\Models\BCL\tr_renter;
use App\Models\User;
use App\Services\BCL\RentalService;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Kos BCL at a glance: room status today (same rules as the room map), cash in/out for the dashboard
 * period vs the previous period, receivables, and what needs attention.
 */
class RingkasanKosWidget implements Widget
{
    private const DAYS_AHEAD = 7;

    public function data(User $user, array $filter): array
    {
        $rental = app(RentalService::class);
        $today = Carbon::today();
        $todayStr = $today->format('Y-m-d');

        // ---- room status today
        $totalRooms = Rooms::count();
        $active = tr_renter::whereNull('cancelled_at')
            ->whereDate('tgl_mulai', '<=', $todayStr)
            ->whereDate('tgl_selesai', '>', $todayStr)
            ->get(['trans_id', 'room_id', 'id_renter', 'tgl_selesai']);
        $activeRooms = $active->pluck('room_id')->unique();
        $overdueRooms = $rental->overstaysByRoom()->keys()->diff($activeRooms);
        $future = tr_renter::whereNull('cancelled_at')->whereDate('tgl_mulai', '>', $todayStr)->get(['room_id', 'id_renter', 'tgl_mulai']);
        $bookedRooms = $future->pluck('room_id')->unique()->diff($activeRooms)->diff($overdueRooms);

        $occupied = $activeRooms->count();
        $overdue = $overdueRooms->count();
        $booked = $bookedRooms->count();
        $vacant = max($totalRooms - $occupied - $overdue - $booked, 0);

        // Ending within a week and not renewed yet (renewal = same renter starting on the end date).
        $expiring = $active->filter(fn ($trx) => Carbon::parse($trx->tgl_selesai)->lte($today->copy()->addDays(self::DAYS_AHEAD))
            && !$future->contains(fn ($b) => $b->id_renter == $trx->id_renter && $b->tgl_mulai === $trx->tgl_selesai))->count();

        // ---- cash for the period (same basis as the room page's monthly summary)
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->startOfDay();
        $days = (int) $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);
        $cash = fn (Carbon $from, Carbon $to) => Fin_jurnal::where('kode_akun', RentalService::ACCOUNT_CASH)
            ->whereBetween('tanggal', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->selectRaw('COALESCE(SUM(debet), 0) as masuk, COALESCE(SUM(kredit), 0) as keluar')
            ->first();
        $now = $cash($start, $end);
        $prev = $cash($prevStart, $prevEnd);
        $rentIncome = (float) Fin_jurnal::where('kode_akun', RentalService::ACCOUNT_REVENUE)->where('pos', 'K')
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])->sum('kredit');

        // ---- receivables + inventory maintenance due
        $unpaid = $rental->unpaidItems();
        $inventory = DB::query()->fromSub(app(InventoriesController::class)->statusQuery(), 's')
            ->selectRaw("SUM(status = 'overdue') as overdue, SUM(status = 'soon') as soon")->first();

        $pct = fn ($n) => Fmt::share($n, $totalRooms);

        return [
            'totalRooms' => $totalRooms,
            'filled' => $occupied + $overdue,
            'occupancy' => $totalRooms > 0 ? (int) round(($occupied + $overdue) / $totalRooms * 100) : 0,
            'segments' => [
                ['label' => 'Dihuni', 'value' => $occupied . ' kamar', 'pct' => $pct($occupied), 'color' => 'var(--dw-series-1)'],
                ['label' => 'Lewat masa sewa', 'value' => $overdue . ' kamar', 'pct' => $pct($overdue), 'color' => 'var(--dw-critical)'],
                ['label' => 'Kosong, ada booking', 'value' => $booked . ' kamar', 'pct' => $pct($booked), 'color' => 'var(--dw-warning)'],
                ['label' => 'Kosong', 'value' => $vacant . ' kamar', 'pct' => $pct($vacant), 'color' => 'var(--dw-other)'],
            ],
            'cashIn' => (float) $now->masuk,
            'cashOut' => (float) $now->keluar,
            'net' => (float) $now->masuk - (float) $now->keluar,
            'rentIncome' => $rentIncome,
            'cashInTrend' => Fmt::trend((float) $now->masuk, (float) $prev->masuk),
            'cashOutTrend' => Fmt::trend((float) $now->keluar, (float) $prev->keluar, false),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'receivable' => (float) $unpaid->sum('kurang'),
            'unpaidCount' => $unpaid->count(),
            'attention' => array_values(array_filter([
                ['icon' => 'alert-octagon', 'tone' => 'critical', 'count' => $overdue, 'title' => 'Lewat masa sewa', 'meta' => 'Belum perpanjang / belum dikonfirmasi keluar', 'href' => route('bcl.rooms')],
                ['icon' => 'clock', 'tone' => 'warning', 'count' => $expiring, 'title' => 'Habis ≤ ' . self::DAYS_AHEAD . ' hari', 'meta' => 'Belum diperpanjang', 'href' => route('bcl.rooms')],
                ['icon' => 'credit-card', 'tone' => 'warning', 'count' => $unpaid->count(), 'title' => 'Belum lunas', 'meta' => 'Sisa ' . Fmt::rp($unpaid->sum('kurang')), 'href' => route('bcl.rooms')],
                ['icon' => 'tool', 'tone' => 'warning', 'count' => (int) $inventory->overdue, 'title' => 'Inventaris telat dirawat', 'meta' => 'Jadwal perawatan rutin terlewat' . ((int) $inventory->soon > 0 ? ' · ' . (int) $inventory->soon . ' lagi jatuh tempo minggu ini' : ''), 'href' => route('bcl.inventories.index')],
            ], fn ($row) => $row['count'] > 0)),
        ];
    }
}
