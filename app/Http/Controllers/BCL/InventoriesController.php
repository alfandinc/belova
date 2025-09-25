<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;

use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\Rooms;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class InventoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Inventory::leftjoin('bcl_rooms as rooms', 'rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftjoin('bcl_fin_jurnal as fin_jurnal', function ($join) {
                $join->on('fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(
                DB::raw('bcl_inventories.inv_number as inv_number'),
                DB::raw('ANY_VALUE(bcl_inventories.id) as id'),
                DB::raw('ANY_VALUE(bcl_inventories.name) as name'),
                DB::raw('ANY_VALUE(bcl_inventories.notes) as notes'),
                DB::raw('ANY_VALUE(bcl_inventories.maintanance_period) as maintanance_period'),
                DB::raw('ANY_VALUE(bcl_inventories.maintanance_cycle) as maintanance_cycle'),
                DB::raw('ANY_VALUE(bcl_inventories.type) as type'),
                DB::raw('ANY_VALUE(bcl_inventories.assigned_to) as assigned_to'),
                DB::raw('ANY_VALUE(rooms.room_name) as room_name'),
                DB::raw('MAX(fin_jurnal.tanggal) as last_maintanance')
            )
            ->groupBy('bcl_inventories.inv_number')
            ->get();
        // return response()->json($data);

        $rooms = Rooms::leftjoin('bcl_room_category as room_category', 'room_category.id_category', '=', 'bcl_rooms.room_category')
            ->select('bcl_rooms.*', 'room_category.category_name')
            ->get();
        $no_inv = $this->get_no_inv();
        return view('bcl.inventories.index')->with('data', $data)->with('rooms', $rooms)->with('no_inv', $no_inv);
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
        $no_inv = $this->get_no_inv();
        try {
            $this->validate($request, [
                'nama' => 'required',
                'tipe_inv' => 'required',
                'kamar' => 'required_if:tipe_inv,==,Private/Room',
                'waktu_perawatan' => 'required_if:perawatan_rutin,==,on',
                'cycle_perawatan' => 'required_if:perawatan_rutin,==,on',
            ]);
            $result = Inventory::create([
                'inv_number' => $no_inv,
                'name' => $request->nama,
                'notes' => $request->keterangan,
                'maintanance_period' => $request->waktu_perawatan,
                'maintanance_cycle' => $request->cycle_perawatan,
                'type' => $request->tipe_inv,
                'assigned_to' => $request->kamar,
            ]);
            return back()->with(['success' => 'Data Inventaris berhasil ditambahkan!']);
        } catch (\Throwable $th) {
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory, Request $request)
    {
        try {
            $data = Inventory::with('room')->where('inv_number', $request->id)->first();
            $history = [];
            foreach (Fin_jurnal::where('kode_subledger', 'like', '%' . $data->inv_number . '%')->orderby('tanggal', 'desc')->orderby('id', 'desc')->get() as $value) {
                array_push($history, $value);
            }
            $data->history = $history;
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory, Request $request)
    {
        try {
            $data = Inventory::findorfail($request->id);
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        try {
            $this->validate($request, [
                'nama' => 'required',
                'tipe_inv' => 'required',
                'kamar' => 'required_if:tipe_inv,==,Private/Room',
                'waktu_perawatan' => 'required_if:perawatan_rutin,==,On',
                'cycle_perawatan' => 'required_if:perawatan_rutin,==,On',
            ]);
            $result = Inventory::findorfail($request->id)->update([
                'name' => $request->nama,
                'notes' => $request->tipe_inv,
                'maintanance_period' => $request->waktu_perawatan,
                'maintanance_cycle' => $request->cycle_perawatan,
                'type' => $request->tipe_inv,
                'assigned_to' => $request->kamar,
            ]);
            return back()->with(['success' => 'Data Inventaris berhasil diubah!']);
        } catch (\Throwable $th) {
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory, Request $request)
    {
        try {
            Inventory::findorfail($request->id)->delete();
            return back()->with(['success' => 'Data Inventaris berhasil dihapus!']);
        } catch (\Throwable $th) {
            return back()->with(['error' => $th->getMessage()]);
        }
    }

    public function get_no_inv()
    {
        $data = DB::select("SELECT CONCAT('IN',DATE_FORMAT(NOW(), '%m%y' ),LPAD(ifnull(max(SUBSTR(inv_number,7)),0)+1,4,0)) as no_inv from bcl_inventories");
        $result = $data[0];
        return $result->no_inv;
    }
}
