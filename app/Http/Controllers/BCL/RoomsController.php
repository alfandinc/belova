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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomsController extends Controller
{
    protected function buildDashboardPayload(): array
    {
        $data = Rooms::leftjoin('bcl_room_category as room_category', 'bcl_rooms.room_category', '=', 'room_category.id_category')
            ->leftjoin('bcl_tr_renter as tr_renter', function ($join) {
                $join->on('bcl_rooms.id', '=', 'tr_renter.room_id')
                    ->where('tr_renter.tgl_mulai', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('tr_renter.tgl_selesai', '>=', Carbon::now()->format('Y-m-d'));
            })->leftJoin('bcl_renter as renter', 'tr_renter.id_renter', '=', 'renter.id')
            ->leftjoin('bcl_fin_jurnal as fin_jurnal', function ($join2) {
                $join2->on('tr_renter.trans_id', '=', 'fin_jurnal.doc_id')
                    ->where('fin_jurnal.identity', '=', 'Sewa Kamar');
            })
            ->select(
                'bcl_rooms.*',
                DB::raw('IFNULL(MAX(tr_renter.harga),0) - IFNULL(SUM(fin_jurnal.kredit),0) as kurang'),
                DB::raw('ANY_VALUE(renter.nama) as nama'),
                DB::raw('ANY_VALUE(tr_renter.trans_id) as trans_id'),
                DB::raw('ANY_VALUE(tr_renter.id_renter) as id_renter'),
                DB::raw('ANY_VALUE(tr_renter.room_id) as room_id'),
                DB::raw('ANY_VALUE(tr_renter.tgl_mulai) as tgl_mulai'),
                DB::raw('ANY_VALUE(tr_renter.tgl_selesai) as tgl_selesai'),
                DB::raw('ANY_VALUE(tr_renter.lama_sewa) as lama_sewa'),
                DB::raw('ANY_VALUE(tr_renter.jangka_sewa) as jangka_sewa'),
                DB::raw('ANY_VALUE(tr_renter.free_sewa) as free_sewa'),
                DB::raw('ANY_VALUE(tr_renter.free_jangka) as free_jangka'),
                DB::raw('ANY_VALUE(room_category.category_name) as category_name')
            )
            ->groupBy('bcl_rooms.id')
            ->get();

        $today = Carbon::now()->format('Y-m-d');

        $futureBookings = tr_renter::with('renter')
            ->whereDate('tgl_mulai', '>', $today)
            ->orderBy('tgl_mulai')
            ->get()
            ->groupBy('room_id');

        $rooms = $data->map(function ($room) use ($futureBookings) {
            $bookings = ($futureBookings->get($room->id) ?? collect())->values();
            $nextBooking = $bookings->first();
            $isOccupied = !empty($room->jangka_sewa);
            $hasBookingQueue = !$isOccupied && $bookings->count() > 0;

            return [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor' => $room->floor,
                'room_category' => $room->room_category,
                'category_name' => $room->category_name,
                'notes' => $room->notes,
                'kurang' => (float) $room->kurang,
                'nama' => $room->nama,
                'trans_id' => $room->trans_id,
                'id_renter' => $room->id_renter,
                'room_id' => $room->room_id,
                'tgl_mulai' => $room->tgl_mulai,
                'tgl_selesai' => $room->tgl_selesai,
                'lama_sewa' => $room->lama_sewa,
                'jangka_sewa' => $room->jangka_sewa,
                'free_sewa' => $room->free_sewa,
                'free_jangka' => $room->free_jangka,
                'booking_count' => $bookings->count(),
                'next_booking_renter' => optional(optional($nextBooking)->renter)->nama,
                'next_booking_start' => optional($nextBooking)->tgl_mulai,
                'next_booking_end' => optional($nextBooking)->tgl_selesai,
                'is_occupied' => $isOccupied,
                'has_booking_queue' => $hasBookingQueue,
            ];
        })->values();

        return [
            'rooms' => $rooms,
            'stats' => [
                'occupied' => $rooms->where('is_occupied', true)->count(),
                'pending' => $rooms->where('has_booking_queue', true)->count(),
                'vacant' => $rooms->where('is_occupied', false)->where('has_booking_queue', false)->count(),
            ],
        ];
    }

    protected function buildFormPayload(): array
    {
        $baseRooms = Rooms::with('category')->with('renter')->orderedForMapping()->get()->map(function ($room) {
            return [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor' => $room->floor,
                'room_category' => $room->room_category,
                'category_name' => optional($room->category)->category_name,
                'renter' => $room->renter ? [
                    'trans_id' => $room->renter->trans_id,
                    'nama' => $room->renter->nama,
                ] : null,
            ];
        })->values();

        $categories = room_category::all()->map(function ($category) {
            return [
                'id' => $category->id_category,
                'name' => $category->category_name,
            ];
        })->values();

        $renters = renter::all()->map(function ($renter) {
            return [
                'id' => $renter->id,
                'nama' => $renter->nama,
                'deposit_balance' => (float) ($renter->deposit_balance ?? 0),
            ];
        })->values();

        $extraPricelists = extra_pricelist::all()->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'harga' => (float) $item->harga,
                'jangka_sewa' => $item->jangka_sewa,
            ];
        })->values();

        return [
            'base_rooms' => $baseRooms,
            'categories' => $categories,
            'renters' => $renters,
            'extra_pricelist' => $extraPricelists,
        ];
    }

    protected function buildDeletedRoomsPayload(): array
    {
        $deleted = Rooms::onlyTrashed()->orderedForMapping()->get()->map(function ($room) {
            return [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor' => $room->floor,
                'notes' => $room->notes,
                'deleted_at' => optional($room->deleted_at)->format('Y-m-d H:i:s'),
                'restore_url' => route('bcl.rooms.restore', $room->id),
            ];
        })->values();

        return [
            'deleted' => $deleted,
        ];
    }

    protected function buildUnpaidTransactionsPayload(): array
    {
        $regularTransactions = Fin_jurnal::leftjoin('bcl_tr_renter as tr_renter', 'tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter as renter', 'renter.id', '=', 'tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as transaksi'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as tanggal'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.identity) as identity'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.catatan) as catatan'),
                DB::raw('ANY_VALUE(renter.nama) as nama'),
                DB::raw('IFNULL(MAX(tr_renter.harga),0) as harga'),
                DB::raw('IFNULL(SUM(kredit),0) AS dibayar'),
                DB::raw('IFNULL(MAX(tr_renter.harga) - SUM(kredit),0) AS kurang')
            )
            ->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar|upgrade kamar')
            ->groupBy('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(tr_renter.harga) - SUM(kredit)) > 0')
            ->orderBy(DB::raw('MAX(bcl_fin_jurnal.tanggal)'), 'DESC')
            ->get()
            ->map(function ($item) {
                $identity = (string) ($item->identity ?? '');
                $isUpgrade = stripos($identity, 'upgrade kamar') !== false;

                return [
                    'transaksi' => $item->transaksi,
                    'tanggal' => $item->tanggal,
                    'nomor' => $item->transaksi,
                    'tipe' => $isUpgrade ? 'Upgrade Kamar' : ($identity === 'Sewa Kamar' ? 'Pendapatan Sewa' : 'Pendapatan Lain'),
                    'catatan' => $item->catatan,
                    'jumlah' => (float) $item->harga,
                    'kurang' => (float) $item->kurang,
                    'section' => $isUpgrade ? 'Upgrade Kamar' : 'Sewa Kamar',
                    'option_label' => trim($item->transaksi . ' - ' . $item->catatan),
                ];
            });

        $extraTransactions = tb_extra_rent::withSum('jurnal as total_kredit', 'kredit')
            ->get()
            ->filter(function ($item) {
                return (($item->harga * $item->lama_sewa * $item->qty) - ($item->total_kredit ?? 0)) > 0;
            })
            ->map(function ($item) {
                $detail = tr_renter::where('trans_id', $item->parent_trans)->with('renter')->first();
                $renterName = optional(optional($detail)->renter)->nama;
                $totalHarga = (float) $item->harga * (float) $item->lama_sewa * (float) $item->qty;
                $kurang = $totalHarga - (float) ($item->total_kredit ?? 0);
                $catatan = trim($item->nama . ' ' . $item->lama_sewa . ' ' . $item->jangka_sewa . ' ' . $renterName);

                return [
                    'transaksi' => $item->kode,
                    'tanggal' => $item->tgl_mulai,
                    'nomor' => $item->kode,
                    'tipe' => 'Tambahan Sewa',
                    'catatan' => $catatan,
                    'jumlah' => $totalHarga,
                    'kurang' => $kurang,
                    'section' => 'Tambahan Sewa',
                    'option_label' => trim($item->kode . ' - ' . $catatan),
                ];
            });

        $items = $regularTransactions
            ->concat($extraTransactions)
            ->sortByDesc(function ($item) {
                return $item['tanggal'] ?? '';
            })
            ->values();

        return [
            'items' => $items,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('bcl.rooms.rooms');
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
        return response()->json($this->buildDeletedRoomsPayload());
    }

    public function unpaidData()
    {
        return response()->json($this->buildUnpaidTransactionsPayload());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'no_kamar'     => 'required|unique:bcl_rooms,room_name',
                'floor'        => 'required|integer|between:1,4',
                'kategori'     => 'required|numeric'
            ]);
            $result = Rooms::create([
                'room_name'     => $request->no_kamar,
                'floor'         => $request->floor,
                'room_category'     => $request->kategori,
                'notes'   => $request->catatan
            ]);
            return redirect()->route('bcl.rooms')->with(['success' => 'Data Kamar berhasil ditambahkan!']);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.rooms')->with(['error' => $th->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $data = Rooms::onlyTrashed()->find($id);
            $data->restore();
            return back()->with('success', 'Data berhasil dikembalikan');
        } catch (\Throwable $th) {
            return back()->with('error', 'Data gagal dikembalikan');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Rooms $rooms)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        try {
            $room = Rooms::find($request->id);
            return response()->json($room);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.rooms')->with(['error' => 'Data tidak ditemukan!']);
        }
    }

    public function wifi($id)
    {
        $room = Rooms::findOrFail($id);
        $wifi = RoomWifi::where('room_id', $room->id)
            ->where('active', true)
            ->latest('id')
            ->first();

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->room_name,
            ],
            'wifi' => $wifi,
        ]);
    }

    public function history($id)
    {
        $room = Rooms::findOrFail($id);

        $transactions = tr_renter::with(['renter', 'jurnal', 'tambahan'])
            ->where('room_id', $room->id)
            ->orderByDesc('tgl_mulai')
            ->get()
            ->map(function ($transaction) {
                $paidTotal = (float) $transaction->jurnal->sum('kredit');
                $extraTotal = (float) $transaction->tambahan->sum('harga');

                return [
                    'trans_id' => $transaction->trans_id,
                    'tanggal' => $transaction->tanggal,
                    'tgl_mulai' => $transaction->tgl_mulai,
                    'tgl_selesai' => $transaction->tgl_selesai,
                    'lama_sewa' => $transaction->lama_sewa,
                    'jangka_sewa' => $transaction->jangka_sewa,
                    'harga' => (float) $transaction->harga,
                    'paid_total' => $paidTotal,
                    'extra_total' => $extraTotal,
                    'renter_name' => optional($transaction->renter)->nama,
                    'notes' => $transaction->catatan,
                ];
            })
            ->values();

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->room_name,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function bookingQueue($id)
    {
        $room = Rooms::findOrFail($id);
        $today = Carbon::now()->format('Y-m-d');

        $transactions = tr_renter::with(['renter', 'jurnal', 'tambahan'])
            ->where('room_id', $room->id)
            ->whereDate('tgl_mulai', '>', $today)
            ->orderBy('tgl_mulai')
            ->get()
            ->map(function ($transaction) {
                $paidTotal = (float) $transaction->jurnal->sum('kredit');
                $extraTotal = (float) $transaction->tambahan->sum('harga');

                return [
                    'trans_id' => $transaction->trans_id,
                    'tanggal' => $transaction->tanggal,
                    'tgl_mulai' => $transaction->tgl_mulai,
                    'tgl_selesai' => $transaction->tgl_selesai,
                    'lama_sewa' => $transaction->lama_sewa,
                    'jangka_sewa' => $transaction->jangka_sewa,
                    'harga' => (float) $transaction->harga,
                    'paid_total' => $paidTotal,
                    'extra_total' => $extraTotal,
                    'renter_name' => optional($transaction->renter)->nama,
                    'notes' => $transaction->catatan,
                ];
            })
            ->values();

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->room_name,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rooms $rooms)
    {
        try {
            $this->validate($request, [
                'no_kamar'     => 'required',
                'floor'        => 'required|integer|between:1,4',
                'kategori'     => 'required|numeric'
            ]);
            $room = Rooms::find($request->id);
            $result = $room->update([
                'room_name'     => $request->no_kamar,
                'floor'         => $request->floor,
                'room_category'     => $request->kategori,
                'notes'   => $request->catatan
            ]);
            return redirect()->route('bcl.rooms')->with(['success' => 'Data Berhasil diubah!']);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.rooms')->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rooms $rooms, Request $request)
    {
        try {
            $room = Rooms::find($request->id);
            $result = $room->delete();
            return redirect()->route('bcl.rooms')->with(['success' => 'Data berhasil dihapus!']);
        } catch (\Throwable $th) {
            return redirect()->route('bcl.rooms')->with(['error' => 'Data gagal dihapus!']);
        }
    }
}
