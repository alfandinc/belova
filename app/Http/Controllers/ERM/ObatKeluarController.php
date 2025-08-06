<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ERM\ResepFarmasi;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;

class ObatKeluarController extends Controller
{
    public function index()
    {
        return view('erm.obat_keluar.index');
    }

    public function data(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        if (!$start || !$end) {
            $start = $end = now()->toDateString();
        }

        $query = DB::table('erm_resepfarmasi as rf')
            ->join('erm_obat as o', 'rf.obat_id', '=', 'o.id')
            ->select(
                'rf.obat_id',
                'o.nama as nama_obat',
                DB::raw('SUM(rf.jumlah) as jumlah')
            )
            ->whereDate('rf.created_at', '>=', $start)
            ->whereDate('rf.created_at', '<=', $end)
            ->groupBy('rf.obat_id', 'o.nama');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->where('o.nama', 'like', "%$search%");
                }
            })
            ->addColumn('detail', function($row) {
                return '<button class="btn btn-sm btn-info btn-detail" data-obat-id="' . $row->obat_id . '">Detail</button>';
            })
            ->rawColumns(['detail'])
            ->make(true);
    }

    /**
     * Show detail modal content for obat keluar
     */
    public function detail(Request $request)
    {
        $obatId = $request->input('obat_id');
        $start = $request->input('start');
        $end = $request->input('end');
        if (!$start || !$end) {
            $start = $end = now()->toDateString();
        }

        $list = \App\Models\ERM\ResepFarmasi::with(['obat', 'visitation.pasien'])
            ->where('obat_id', $obatId)
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderByDesc('created_at')
            ->get();

        return view('erm.obat_keluar.detail', compact('list'))->render();
    }
    }
