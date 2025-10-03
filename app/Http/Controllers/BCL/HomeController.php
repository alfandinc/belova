<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\renter;
use App\Models\BCL\tr_renter;
use App\Models\BCL\Rooms;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
    $data = Rooms::with('category')->with('renter')->get();
        $room_kosong = 0;
        foreach ($data as $value) {
            if ($value->renter == null) {
                $room_kosong++;
            }
        }
        $response = new \stdClass();
        $rooms = new \stdClass();
        $rooms->total = $data->count();
        $rooms->kosong = $data->count()-$room_kosong;
        $response->rooms = $rooms;
        // return response()->json($data);
        $belum_lunas = Fin_jurnal::leftjoin('bcl_tr_renter as tr_renter', 'tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter as renter', 'renter.id', '=', 'tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as tanggal'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.identity) as identity'),
                DB::raw('ANY_VALUE(bcl_fin_jurnal.catatan) as catatan'),
                DB::raw('ANY_VALUE(renter.nama) as nama'),
                DB::raw('ANY_VALUE(renter.id) as id'),
                DB::raw('IFNULL(MAX(tr_renter.harga),0) as harga'),
                DB::raw('IFNULL(SUM( kredit ),0) AS dibayar'),
                DB::raw('IFNULL(MAX(tr_renter.harga) - SUM( kredit ),0) AS kurang')
            )->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar')
            ->groupby('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(tr_renter.harga) - SUM(kredit)) > 0')
            ->orderby(DB::raw('MAX(bcl_fin_jurnal.tanggal)'), 'DESC')
            ->get();
        $response->belum_lunas = $belum_lunas->count();

        $inventory = Inventory::leftjoin('bcl_rooms as rooms', 'rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftjoin('bcl_fin_jurnal as fin_jurnal', function ($join) {
                $join->on('fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(DB::raw('bcl_inventories.*'), 'rooms.room_name', DB::raw('max(tanggal) as last_maintanance'))
            ->groupby('bcl_inventories.inv_number')
            ->get();

        $needed_maintanance = 0;
        foreach ($inventory as $data) {
            if ($data->last_maintanance != null && $data->maintanance_cycle != null) {
                $period = (int) $data->maintanance_period;
                if ($data->maintanance_cycle == 'Minggu') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addWeeks($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Bulan') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addMonths($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Tahun') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addYears($period)->format('Y-m-d');
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

        $room_stat = Rooms::leftjoin('bcl_tr_renter as tr_renter', function ($join) {
            $join->on('tr_renter.room_id', '=', 'bcl_rooms.id');
            $join->where(DB::raw('year(tr_renter.tgl_mulai)'), '=', Carbon::now()->format('Y'));
        })->select('bcl_rooms.*', DB::raw('sum(tr_renter.harga) as total_value'))->groupby('bcl_rooms.id')->orderby('total_value', 'DESC')->get();
        $stat = new \stdClass();
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
