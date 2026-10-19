<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\BCL\Rooms;
use App\Models\BCL\tr_renter;
use App\Models\User;
use App\Services\BCL\RentalService;
use Carbon\Carbon;

/** Kos BCL: rentals ending within the next week, occupancy and outstanding receivables. */
class SewaKosWidget implements Widget
{
    private const DAYS_AHEAD = 7;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();
        $active = tr_renter::with(['renter', 'room'])
            ->whereDate('tgl_mulai', '<=', $today->format('Y-m-d'))
            ->whereDate('tgl_selesai', '>', $today->format('Y-m-d'))
            ->get();
        $nextBookings = tr_renter::whereDate('tgl_mulai', '>', $today->format('Y-m-d'))
            ->get(['id_renter', 'room_id', 'tgl_mulai']);

        $expiring = $active
            ->filter(fn ($trx) => Carbon::parse($trx->tgl_selesai)->lte($today->copy()->addDays(self::DAYS_AHEAD)))
            ->map(function ($trx) use ($today, $nextBookings) {
                $end = Carbon::parse($trx->tgl_selesai);

                return (object) [
                    'room' => optional($trx->room)->room_name,
                    'renter' => optional($trx->renter)->nama,
                    'phone' => optional($trx->renter)->phone,
                    'end' => $end,
                    'days' => (int) $today->diffInDays($end),
                    // Already extended when the same renter has a booking starting on the end date.
                    'extended' => $nextBookings->contains(fn ($b) => $b->id_renter == $trx->id_renter && $b->tgl_mulai === $trx->tgl_selesai),
                ];
            })
            ->sortBy('days')
            ->values();

        $overdue = app(RentalService::class)->overstaysByRoom()
            ->reject(fn ($trx) => $active->contains('room_id', $trx->room_id))
            ->map(fn ($trx) => (object) [
                'room' => optional($trx->room)->room_name,
                'renter' => optional($trx->renter)->nama,
                'phone' => optional($trx->renter)->phone,
                'end' => Carbon::parse($trx->tgl_selesai),
                'days' => (int) Carbon::parse($trx->tgl_selesai)->diffInDays($today),
            ])
            ->sortByDesc('days')
            ->values();

        $totalRooms = Rooms::count();
        $unpaid = app(RentalService::class)->unpaidItems();

        return [
            'expiring' => $expiring->all(),
            'overdue' => $overdue->all(),
            'daysAhead' => self::DAYS_AHEAD,
            'occupancy' => $totalRooms > 0 ? (int) round($active->pluck('room_id')->unique()->count() / $totalRooms * 100) : 0,
            'receivable' => (float) $unpaid->sum('kurang'),
            'unpaidCount' => $unpaid->count(),
        ];
    }
}
