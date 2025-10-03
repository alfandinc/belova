<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\renter;
use App\Models\BCL\room_category;
use App\Models\BCL\Rooms;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Rooms::leftjoin('bcl_room_category as room_category', 'bcl_rooms.room_category', '=', 'room_category.id_category')
            ->leftjoin('bcl_tr_renter as tr_renter', function ($join) {
                $join->on('bcl_rooms.id', '=', 'tr_renter.room_id')
                    ->where('tr_renter.tgl_mulai', '<=',  Carbon::now()->format('Y-m-d'))
                    ->where('tr_renter.tgl_selesai', '>=',  Carbon::now()->format('Y-m-d'));
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
        // return dd($data);
        $category = room_category::all();
        // $rooms = Rooms::leftjoin('room_category', 'rooms.room_category', '=', 'room_category.id_category')
        //     ->select('rooms.*', 'room_category.category_name as category_name')->get();
    $rooms = Rooms::with('category')->get();
        $deleted = Rooms::onlyTrashed()->get();
        $renter = renter::all();
        // return response()->json($rooms);
        return view('bcl.rooms.rooms')->with('data', $data)->with('category', $category)
            ->with('renter', $renter)->with('base_room', $rooms)->with('deleted', $deleted);
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
                'kategori'     => 'required|numeric'
            ]);
            $result = Rooms::create([
                'room_name'     => $request->no_kamar,
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rooms $rooms)
    {
        try {
            $this->validate($request, [
                'no_kamar'     => 'required',
                'kategori'     => 'required|numeric'
            ]);
            $room = Rooms::find($request->id);
            $result = $room->update([
                'room_name'     => $request->no_kamar,
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
