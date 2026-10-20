<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use App\Models\BCL\extra_pricelist;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\renter;
use App\Models\BCL\room_category;
use App\Models\BCL\RoomWifi;
use App\Models\BCL\Rooms;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use App\Services\BCL\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomsController extends Controller
{
    public function __construct(protected RentalService $rental)
    {
    }

    public function index()
    {
        // The first payload is embedded in the page so the room map renders without an extra request.
        return view('bcl.rooms.rooms', ['initial' => $this->buildDashboardPayload()]);
    }

    public function data()
    {
        return response()->json($this->buildDashboardPayload());
    }

    public function formData()
    {
        return response()->json($this->buildFormPayload());
    }

    public function deletedData()
    {
        $deleted = Rooms::onlyTrashed()->orderedForMapping()->get()->map(fn ($room) => [
            'id' => $room->id,
            'room_name' => $room->room_name,
            'floor' => $room->floor,
            'notes' => $room->notes,
            'deleted_at' => optional($room->deleted_at)->format('Y-m-d H:i:s'),
            'restore_url' => route('bcl.rooms.restore', $room->id),
        ])->values();

        return response()->json(['deleted' => $deleted]);
    }

    public function unpaidData()
    {
        return response()->json(['items' => $this->rental->unpaidItems()]);
    }

    public function extraBedData()
    {
        return response()->json($this->buildExtraBedPayload());
    }

    public function store(Request $request)
    {
        return $this->saveRoom($request, new Rooms(), 'Data Kamar berhasil ditambahkan!');
    }

    public function update(Request $request)
    {
        $room = Rooms::find($request->id);
        if (!$room) {
            return $this->respond($request, 'Data kamar tidak ditemukan.', 404);
        }

        return $this->saveRoom($request, $room, 'Data Berhasil diubah!');
    }

    public function edit(Request $request)
    {
        return response()->json(Rooms::find($request->id));
    }

    public function restore($id, Request $request)
    {
        $room = Rooms::onlyTrashed()->find($id);
        if (!$room) {
            return $this->respond($request, 'Data kamar tidak ditemukan.', 404);
        }
        $room->restore();

        return $this->respond($request, 'Data berhasil dikembalikan');
    }

    public function destroy(Request $request)
    {
        $room = Rooms::find($request->id);
        if (!$room) {
            return $this->respond($request, 'Data kamar tidak ditemukan.', 404);
        }

        $hasRental = tr_renter::where('room_id', $room->id)
            ->whereDate('tgl_selesai', '>', Carbon::today()->format('Y-m-d'))
            ->exists();
        if ($hasRental) {
            return $this->respond($request, 'Kamar masih memiliki penyewa aktif atau antrian booking, tidak bisa dihapus.', 422);
        }
        $room->delete();

        return $this->respond($request, 'Data berhasil dihapus!');
    }

    /** Suggested price for a custom stay in a room (Sewa form, "Durasi custom"). */
    public function priceSuggestion(Request $request)
    {
        $this->validate($request, [
            'room' => 'required|exists:bcl_rooms,id',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);
        $room = Rooms::findOrFail($request->room);

        return response()->json($this->rental->suggestPrice($room->room_category, $request->start, $request->end));
    }

    public function wifi($id)
    {
        $room = Rooms::findOrFail($id);
        $wifi = RoomWifi::where('room_id', $room->id)->where('active', true)->latest('id')->first();

        return response()->json([
            'room' => ['id' => $room->id, 'name' => $room->room_name],
            'wifi' => $wifi,
        ]);
    }

    public function history($id)
    {
        return $this->transactionList($id, false);
    }

    public function bookingQueue($id)
    {
        return $this->transactionList($id, true);
    }

    // ------------------------------------------------------------ builders

    protected function transactionList($roomId, bool $futureOnly)
    {
        $room = Rooms::findOrFail($roomId);
        $transactions = tr_renter::with(['renter', 'tambahan'])
            ->where('room_id', $room->id)
            ->when($futureOnly, fn ($q) => $q->whereDate('tgl_mulai', '>', Carbon::today()->format('Y-m-d'))->orderBy('tgl_mulai'))
            ->when(!$futureOnly, fn ($q) => $q->orderByDesc('tgl_mulai'))
            ->get();
        $paid = $this->rental->paidByDoc($transactions->pluck('trans_id')->merge($transactions->flatMap->tambahan->pluck('kode')));

        return response()->json([
            'room' => ['id' => $room->id, 'name' => $room->room_name],
            'transactions' => $transactions->map(function ($trx) use ($paid) {
                return [
                    'trans_id' => $trx->trans_id,
                    'tanggal' => $trx->tanggal,
                    'tgl_mulai' => $trx->tgl_mulai,
                    'tgl_selesai' => $trx->tgl_selesai,
                    'lama_sewa' => $trx->lama_sewa,
                    'jangka_sewa' => $trx->jangka_sewa,
                    'harga' => (float) $trx->harga,
                    'extra_total' => (float) $trx->tambahan->sum(fn ($e) => $this->rental->extraTotal($e)),
                    'paid_total' => (float) ($paid[$trx->trans_id] ?? 0) + (float) $trx->tambahan->sum(fn ($e) => $paid[$e->kode] ?? 0),
                    'renter_name' => optional($trx->renter)->nama,
                    'notes' => $trx->catatan,
                    'bukti_transfer_url' => $trx->bukti_transfer ? asset('storage/' . ltrim($trx->bukti_transfer, '/')) : null,
                ];
            })->values(),
        ]);
    }

    protected function buildDashboardPayload(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $rooms = Rooms::with('category')->orderedForMapping()->get();
        $active = tr_renter::with('renter')
            ->whereDate('tgl_mulai', '<=', $today)
            ->whereDate('tgl_selesai', '>', $today)
            ->get()
            ->keyBy('room_id');
        // Ended but not extended/checked out: the tenant still lives there (lewat masa sewa).
        $overstays = $this->rental->overstaysByRoom()->reject(fn ($o) => $active->has($o->room_id));
        $paid = $this->rental->paidByDoc($active->pluck('trans_id')->merge($overstays->pluck('trans_id')));
        $futureBookings = tr_renter::with('renter')
            ->whereNull('cancelled_at')
            ->whereDate('tgl_mulai', '>', $today)
            ->orderBy('tgl_mulai')
            ->get()
            ->groupBy('room_id');
        $extraBedByRoom = $this->buildActiveExtraBedByRoom();
        $livingTrx = $active->concat($overstays);
        $guarantees = $this->rental->guaranteeBalances($livingTrx->pluck('id_renter'));
        $checkins = \App\Models\BCL\RoomInspection::whereIn('trans_id', $livingTrx->pluck('trans_id'))->where('type', 'checkin')->pluck('trans_id')->flip();

        $today = Carbon::today();
        $rooms = $rooms->map(function ($room) use ($active, $overstays, $paid, $futureBookings, $extraBedByRoom, $today, $guarantees, $checkins) {
            $current = $active->get($room->id);
            $over = $current ? null : $overstays->get($room->id);
            $trx = $current ?: $over;
            $bookings = ($futureBookings->get($room->id) ?? collect())->values();
            $nextBooking = $bookings->first();
            // A transaction without revenue lines (legacy room-change record) has nothing to collect.
            $kurang = $trx && $paid->has($trx->trans_id) ? max((float) $trx->harga - $paid[$trx->trans_id], 0) : 0;
            $daysLeft = $trx ? (int) $today->diffInDays(Carbon::parse($trx->tgl_selesai), false) : null;
            $renter = optional($trx)->renter;

            return [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor' => $room->floor,
                'room_category' => $room->room_category,
                'category_name' => optional($room->category)->category_name,
                'notes' => $room->notes,
                'status' => $current ? 'occupied' : ($over ? 'overdue' : ($bookings->count() ? 'booked' : 'vacant')),
                'is_overdue' => (bool) $over,
                'daily_rate' => $over ? $this->rental->dailyRate($over->loadMissing('room')) : null,
                'jaminan' => $trx ? (float) ($guarantees[$trx->id_renter] ?? 0) : 0,
                'has_checkin' => $trx ? $checkins->has($trx->trans_id) : false,
                'days_over' => $over ? (int) Carbon::parse($over->tgl_selesai)->diffInDays($today) : 0,
                'kurang' => $kurang,
                'harga' => (float) optional($trx)->harga,
                'paid' => $trx ? (float) ($paid[$trx->trans_id] ?? 0) : 0,
                'nama' => optional($renter)->nama,
                'phone' => optional($renter)->phone,
                'deposit' => (float) optional($renter)->deposit_balance,
                'trans_id' => optional($trx)->trans_id,
                'identity' => optional($trx)->identity,
                'id_renter' => optional($trx)->id_renter,
                'tgl_mulai' => optional($trx)->tgl_mulai,
                'tgl_selesai' => optional($trx)->tgl_selesai,
                'days_left' => $daysLeft,
                'is_expiring' => $current && $daysLeft <= 7,
                'lama_sewa' => optional($trx)->lama_sewa,
                'jangka_sewa' => optional($trx)->jangka_sewa,
                'catatan' => optional($trx)->catatan,
                // Renewal already booked: same renter starting when the current rental ends.
                'is_extended' => $trx ? $bookings->contains(fn ($b) => $b->id_renter == $trx->id_renter && $b->tgl_mulai === $trx->tgl_selesai) : false,
                'bookings' => $bookings->take(5)->map(fn ($b) => [
                    'trans_id' => $b->trans_id,
                    'renter' => optional($b->renter)->nama,
                    'start' => $b->tgl_mulai,
                    'end' => $b->tgl_selesai,
                ])->values(),
                'booking_count' => $bookings->count(),
                'next_booking_renter' => optional(optional($nextBooking)->renter)->nama,
                'next_booking_start' => optional($nextBooking)->tgl_mulai,
                'is_occupied' => (bool) $trx,
                'has_booking_queue' => !$trx && $bookings->count() > 0,
                'extra_bed_count' => (int) ($extraBedByRoom[$room->id]['count'] ?? 0),
                'extra_bed_asset_codes' => array_values($extraBedByRoom[$room->id]['asset_codes'] ?? []),
            ];
        })->values();

        $occupied = $rooms->where('is_occupied', true)->count();

        return [
            'rooms' => $rooms,
            'stats' => [
                'all' => $rooms->count(),
                'occupied' => $occupied,
                'pending' => $rooms->where('has_booking_queue', true)->count(),
                'vacant' => $rooms->where('status', 'vacant')->count(),
                'expiring' => $rooms->where('is_expiring', true)->where('is_extended', false)->count(),
                'unpaid' => $rooms->where('kurang', '>', 0)->count(),
                'overdue' => $rooms->where('status', 'overdue')->count(),
            ],
            'summary' => $this->buildMonthlySummary($rooms->count(), $occupied),
        ];
    }

    /** Occupancy and cash figures for the current month, shown above the room map. */
    protected function buildMonthlySummary(int $totalRooms, int $occupied): array
    {
        $today = Carbon::today();
        $cash = Fin_jurnal::whereBetween('tanggal', [$today->copy()->startOfMonth()->format('Y-m-d'), $today->copy()->endOfMonth()->format('Y-m-d')])
            ->where('kode_akun', RentalService::ACCOUNT_CASH)
            ->selectRaw('COALESCE(SUM(debet), 0) as masuk, COALESCE(SUM(kredit), 0) as keluar')
            ->first();

        $unpaid = $this->rental->unpaidItems();
        $extraBeds = $this->rental->extraBedCounts();

        return [
            'month' => $today->translatedFormat('F Y'),
            'occupancy' => $totalRooms > 0 ? (int) round($occupied / $totalRooms * 100) : 0,
            'income' => (float) $cash->masuk,
            'expense' => (float) $cash->keluar,
            'receivable' => (float) $unpaid->sum('kurang'),
            'unpaid_count' => $unpaid->count(),
            'extra_bed_available' => $extraBeds['available'],
            'extra_bed_total' => $extraBeds['total'],
        ];
    }

    protected function buildActiveExtraBedByRoom(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $byRoom = [];

        $extras = tb_extra_rent::with(['assetAssignments.asset', 'parentTransaction.room'])
            ->whereDate('tgl_mulai', '<=', $today)
            ->whereDate('tgl_selesai', '>', $today)
            ->get();
        $rooms = $this->rental->activeRoomsByRenter($extras->map(fn ($e) => optional($e->parentTransaction)->id_renter));

        $extras->each(function ($extraRent) use (&$byRoom, $rooms) {
                $parent = $extraRent->parentTransaction;
                $roomId = optional($parent ? ($rooms[$parent->id_renter] ?? $parent->room) : null)->id;
                if (!$roomId) {
                    return;
                }
                $codes = $extraRent->assetAssignments->map(fn ($a) => optional($a->asset)->asset_code)->filter()->values()->all();
                $byRoom[$roomId]['count'] = ($byRoom[$roomId]['count'] ?? 0) + (count($codes) ?: (int) $extraRent->qty);
                $byRoom[$roomId]['asset_codes'] = array_values(array_unique(array_merge($byRoom[$roomId]['asset_codes'] ?? [], $codes)));
            });

        return $byRoom;
    }

    protected function buildFormPayload(): array
    {
        return [
            'base_rooms' => Rooms::with(['category', 'renter'])->orderedForMapping()->get()->map(fn ($room) => [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor' => $room->floor,
                'room_category' => $room->room_category,
                'category_name' => optional($room->category)->category_name,
                'renter' => $room->renter ? ['trans_id' => $room->renter->trans_id, 'nama' => $room->renter->nama] : null,
            ])->values(),
            'categories' => room_category::all()->map(fn ($c) => ['id' => $c->id_category, 'name' => $c->category_name])->values(),
            'renters' => renter::orderBy('nama')->get()->map(fn ($r) => [
                'id' => $r->id,
                'nama' => $r->nama,
                'deposit_balance' => (float) ($r->deposit_balance ?? 0),
            ])->values(),
            'extra_pricelist' => extra_pricelist::all()->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'harga' => (float) $item->harga,
                'jangka_sewa' => $item->jangka_sewa,
                'tracked_inventory' => $item->requiresExtraBedTracking(),
            ])->values(),
        ];
    }

    protected function buildExtraBedPayload(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $assets = $this->rental->extraBedAssets();
        $extras = tb_extra_rent::with(['assetAssignments.asset', 'parentTransaction.room', 'parentTransaction.renter'])
            ->orderByDesc('tgl_mulai')
            ->get();
        $paid = $this->rental->paidByDoc($extras->pluck('kode'));
        $activeRooms = $this->rental->activeRoomsByRenter($extras->map(fn ($e) => optional($e->parentTransaction)->id_renter));

        $transactions = $extras->map(function ($item) use ($today, $paid, $activeRooms) {
            $total = $this->rental->extraTotal($item);
            $dibayar = (float) ($paid[$item->kode] ?? 0);

            return [
                'kode' => $item->kode,
                'nama' => $item->nama,
                'qty' => (int) $item->qty,
                'lama_sewa' => (int) $item->lama_sewa,
                'jangka_sewa' => $item->jangka_sewa,
                'tgl_mulai' => $item->tgl_mulai,
                'tgl_selesai' => $item->tgl_selesai,
                'room_name' => optional($item->parentTransaction ? ($activeRooms[$item->parentTransaction->id_renter] ?? $item->parentTransaction->room) : null)->room_name,
                'renter_name' => optional(optional($item->parentTransaction)->renter)->nama,
                'asset_codes' => $item->assetAssignments->map(fn ($a) => optional($a->asset)->asset_code)->filter()->values()->all(),
                'total' => $total,
                'dibayar' => $dibayar,
                'kurang' => max($total - $dibayar, 0),
                'status' => $item->tgl_mulai > $today ? 'Akan Datang' : ($item->tgl_selesai > $today ? 'Aktif' : 'Selesai'),
            ];
        })->values();

        return [
            'summary' => [
                'total' => $assets->count(),
                'available' => $assets->where('status', 'Tersedia')->count(),
                'in_use' => $assets->where('status', 'Dipakai')->count(),
            ],
            'assets' => $assets,
            'transactions' => $transactions,
        ];
    }

    // ------------------------------------------------------------ helpers

    protected function saveRoom(Request $request, Rooms $room, string $message)
    {
        try {
            $this->validate($request, [
                'no_kamar' => ['required', Rule::unique('bcl_rooms', 'room_name')->ignore($room->id)->whereNull('deleted_at')],
                'floor' => 'required|integer|between:1,10',
                'kategori' => 'required|exists:bcl_room_category,id_category',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respond($request, collect($e->errors())->flatten()->first(), 422);
        }

        $room->fill([
            'room_name' => $request->no_kamar,
            'floor' => $request->floor,
            'room_category' => $request->kategori,
            'notes' => $request->catatan,
        ])->save();

        return $this->respond($request, $message, 200, $room->only(['id', 'room_name', 'floor', 'room_category', 'notes']));
    }

    protected function respond(Request $request, string $message, int $status = 200, array $data = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_filter(['message' => $message, 'data' => $data]), $status);
        }

        return redirect()->route('bcl.rooms')->with($status < 300 ? 'success' : 'error', $message);
    }
}
