<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;

use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\Rooms;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BCL\InventoryMaintenance;
use Carbon\Carbon;

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
        // compute next maintenance date and remaining days/badge for each grouped record
        foreach ($data as $d) {
            $d->next_maintanance = null;
            $d->remaining_badge = '';
            if (!empty($d->last_maintanance) && !empty($d->maintanance_cycle) && !empty($d->maintanance_period)) {
                $period = (int) $d->maintanance_period;
                try {
                    if ($d->maintanance_cycle == 'Minggu') {
                        $next = Carbon::parse($d->last_maintanance)->addWeeks($period)->format('Y-m-d');
                    } else if ($d->maintanance_cycle == 'Bulan') {
                        $next = Carbon::parse($d->last_maintanance)->addMonths($period)->format('Y-m-d');
                    } else if ($d->maintanance_cycle == 'Tahun') {
                        $next = Carbon::parse($d->last_maintanance)->addYears($period)->format('Y-m-d');
                    } else {
                        $next = null;
                    }
                    if ($next) {
                        $d->next_maintanance = $next;
                        // compute signed remaining days (negative => overdue)
                        $remaining = (int) Carbon::now()->diffInDays(Carbon::parse($next), false);
                        if ($remaining < 0) {
                            // overdue: show danger blinking badge with days overdue
                            $overdue = abs($remaining);
                            $d->remaining_badge = '<span class="badge badge-danger faa faa-flash animated">Terlambat ' . $overdue . ' Hari</span>';
                        } elseif ($remaining <= 7) {
                            // due within 7 days: show blinking warning badge
                            $d->remaining_badge = '<span class="badge badge-warning faa faa-flash animated">' . $remaining . ' Hari lagi</span>';
                        } else {
                            // not urgent
                            $d->remaining_badge = '<span class="badge badge-outline-dark">' . $remaining . ' Hari lagi</span>';
                        }
                    }
                } catch (\Exception $ex) {
                    // leave next_maintanance null on parse errors
                    $d->next_maintanance = null;
                    $d->remaining_badge = '';
                }
            }
        }
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

            // Return inventory plus its maintenance records (prefer InventoryMaintenance records)
            $history = InventoryMaintenance::where('inv_number', $request->id)
                ->orderBy('tanggal', 'desc')
                ->get();

            $data->history = $history;
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    /**
     * Update a maintenance record (AJAX)
     */
    public function updateMaintenance(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'tanggal' => 'required|date',
            'catatan' => 'required',
            'nominal' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $maint = InventoryMaintenance::findOrFail($request->id);

            // update the maintenance record
            $maint->tanggal = $request->tanggal;
            $maint->description = $request->catatan;
            $maint->cost = $request->nominal ?? 0;
            if ($request->vendor_name) $maint->vendor_name = $request->vendor_name;
            $maint->save();

            // update related journal entries (by doc_id)
            if (!empty($maint->doc_id)) {
                // debit entry (linked to inv via kode_subledger)
                Fin_jurnal::where('doc_id', $maint->doc_id)->update([
                    'tanggal' => $request->tanggal,
                    'debet' => $request->nominal ?? 0,
                    'kredit' => 0,
                    'kode_subledger' => $maint->inv_number,
                    'catatan' => $request->catatan,
                    'user_id' => Auth::id(),
                ]);
                // credit counterpart (same doc_id, opposite pos)
                Fin_jurnal::where('doc_id', $maint->doc_id)->where('pos', 'K')->update([
                    'tanggal' => $request->tanggal,
                    'kredit' => $request->nominal ?? 0,
                    'debet' => 0,
                    'catatan' => $request->catatan,
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $maint]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Delete a maintenance record (AJAX)
     */
    public function deleteMaintenance(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $maint = InventoryMaintenance::findOrFail($request->id);

            // delete related journal entries by doc_id
            if (!empty($maint->doc_id)) {
                Fin_jurnal::where('doc_id', $maint->doc_id)->delete();
            }

            // soft-delete the maintenance record
            $maint->delete();

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
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
                // inv_number must be present and unique except for the current record
                'inv_number' => 'required|unique:bcl_inventories,inv_number,' . $request->id,
                'kamar' => 'required_if:tipe_inv,==,Private/Room',
                'waktu_perawatan' => 'required_if:perawatan_rutin,==,On',
                'cycle_perawatan' => 'required_if:perawatan_rutin,==,On',
            ]);

            // Update including inv_number and correct notes field
            $result = Inventory::findorfail($request->id)->update([
                'inv_number' => $request->inv_number,
                'name' => $request->nama,
                'notes' => $request->keterangan,
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

    /**
     * Store a maintenance record in the finance journal and link it to an inventory number
     */
    public function storeMaintenance(Request $request)
    {
        $this->validate($request, [
            'inv_number' => 'required',
            'tanggal' => 'required|date',
            'catatan' => 'required',
            'nominal' => 'nullable|numeric',
            'vendor_name' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $inv = Inventory::where('inv_number', $request->inv_number)->first();

            // generate doc id and no_jurnal similar to FinJurnalController
            $doc_id = 'MAINT' . time();
            $nj = DB::select("SELECT CONCAT(DATE_FORMAT(NOW(), '%y' ),LPAD(ifnull(max(SUBSTR(no_jurnal,3)),0)+1,7,0)) as no_jurnal from bcl_fin_jurnal");
            $no_jurnal = $nj[0]->no_jurnal ?? time();

            // create maintenance record
            $maint = InventoryMaintenance::create([
                'inventory_id' => $inv?$inv->id:null,
                'inv_number' => $request->inv_number,
                'tanggal' => $request->tanggal,
                'description' => $request->catatan,
                'cost' => $request->nominal ?? 0,
                'vendor_name' => $request->vendor_name ?? null,
                'doc_id' => $doc_id,
                'created_by' => Auth::id()
            ]);

            // Create corresponding Fin_jurnal entries (double-entry)
            // Credit cash/bank (1-10101)
            $k = Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tanggal,
                'kode_akun' => '1-10101',
                'debet' => 0,
                'kredit' => $request->nominal ?? 0,
                'kode_subledger' => null,
                'catatan' => $request->catatan,
                'index_kas' => 0,
                'doc_id' => $doc_id,
                'identity' => 'Pengeluaran',
                'pos' => 'K',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);

            // Debit expense (5-10101) and link to inv_number
            $d = Fin_jurnal::create([
                'no_jurnal' => $no_jurnal,
                'tanggal' => $request->tanggal,
                'kode_akun' => '5-10101',
                'debet' => $request->nominal ?? 0,
                'kredit' => 0,
                'kode_subledger' => $request->inv_number,
                'catatan' => $request->catatan,
                'index_kas' => 0,
                'doc_id' => $doc_id,
                'identity' => 'Pengeluaran',
                'pos' => 'D',
                'user_id' => Auth::id(),
                'csrf' => time()
            ]);

            // update maintenance with journal id
            $maint->journal_id = $d->id;
            $maint->save();

            DB::commit();

            return response()->json(['success' => true, 'data' => $maint]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
