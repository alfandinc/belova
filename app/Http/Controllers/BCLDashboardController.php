<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\renter;
use App\Models\BCL\tr_renter;
use App\Models\BCL\Rooms;
use App\Http\Controllers\BCL\tr_renterController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BCLDashboardController extends Controller
{
    // public function index()
    // {
        

    //     return view('bcl.home');
    // }
    public function index()
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }
        $data = Rooms::with('category')->with('renter')->get();
        $room_used = 0;
        foreach ($data as $value) {
            if ($value->renter != null) {
                $room_used++;
            }
        }
        $response = new \stdClass();
        $rooms = new \stdClass();
        $rooms->total = $data->count();
        $rooms->used = $room_used;
        $response->rooms = $rooms;

        // Use aggregated columns to be compatible with ONLY_FULL_GROUP_BY
        $belum_lunas = Fin_jurnal::leftjoin('bcl_tr_renter', 'bcl_tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter', 'bcl_renter.id', '=', 'bcl_tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as last_tanggal'),
                DB::raw('MAX(bcl_renter.nama) as nama'),
                DB::raw('MAX(bcl_renter.id) as renter_id'),
                DB::raw('MAX(bcl_tr_renter.harga) as harga'),
                DB::raw('IFNULL(SUM(kredit),0) AS dibayar'),
                DB::raw('IFNULL(MAX(bcl_tr_renter.harga) - SUM(kredit),0) AS kurang')
            )->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar')
            ->groupBy('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(bcl_tr_renter.harga) - SUM(kredit)) > 0')
            ->orderByRaw('MAX(bcl_fin_jurnal.tanggal) DESC')
            ->get();
        $response->belum_lunas = $belum_lunas->count();

        // Select only aggregated or grouped columns to be compatible with ONLY_FULL_GROUP_BY
        $inventory = Inventory::leftjoin('bcl_rooms', 'bcl_rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftjoin('bcl_fin_jurnal', function ($join) {
                $join->on('bcl_fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(
                DB::raw('bcl_inventories.inv_number as inv_number'),
                DB::raw('MAX(bcl_inventories.id) as inventory_id'),
                DB::raw('MAX(bcl_inventories.name) as inv_name'),
                DB::raw('MAX(bcl_inventories.assigned_to) as assigned_to'),
                DB::raw('MAX(bcl_rooms.room_name) as room_name'),
                DB::raw('MAX(bcl_inventories.maintanance_cycle) as maintanance_cycle'),
                DB::raw('MAX(bcl_inventories.maintanance_period) as maintanance_period'),
                DB::raw('MAX(tanggal) as last_maintanance')
            )
            ->groupBy('bcl_inventories.inv_number')
            ->get();

        $needed_maintanance = 0;
        foreach ($inventory as $data) {
            if ($data->last_maintanance != null && $data->maintanance_cycle != null) {
                if ($data->maintanance_cycle == 'Minggu') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addWeeks($data->maintanance_period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Bulan') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addMonths($data->maintanance_period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Tahun') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addYears($data->maintanance_period)->format('Y-m-d');
                    $remaining =  Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                }
            } else {
                $next_maintanance = null;
            }
            if ($next_maintanance != null && $remaining <= 7) {
                $needed_maintanance++;
            }
        }
        $response->needed_maintanance = $needed_maintanance;

        $room_stat = Rooms::leftjoin('bcl_tr_renter', function ($join) {
            $join->on('bcl_tr_renter.room_id', '=', 'bcl_rooms.id');
            $join->where(DB::raw('year(bcl_tr_renter.tgl_mulai)'), '=', Carbon::now()->format('Y'));
        })->select('bcl_rooms.id', 'bcl_rooms.room_name', DB::raw('sum(bcl_tr_renter.harga) as total_value'))->groupby('bcl_rooms.id')->orderby('total_value', 'DESC')->get();
        $stat = new \stdClass();
        $stat->room_name = [];
        $stat->total_value = [];
        foreach ($room_stat->take(10) as $data) {
            $stat->room_name[] = $data->room_name;
            $stat->total_value[] = $data->total_value;
        }
        $response->room_stat = $stat;

        $ranking_penyewa = app(tr_renterController::class)->ranking_penyewa();
        $response->ranking_penyewa = $ranking_penyewa;
        // return response()->json($response);
        // $group_jenis_kelamin = renter::with('current_room')
        // ->sum('')
        // ->get();
        // return response()->json($group_jenis_kelamin);
        return view('bcl.home')->with('response', (object)$response);
    }
}
