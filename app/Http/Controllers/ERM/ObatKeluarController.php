<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ResepFarmasi;
use Yajra\DataTables\DataTables;

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

        $query = ResepFarmasi::with('obat')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('obat_id, SUM(jumlah) as jumlah')
            ->groupBy('obat_id');

        return DataTables::of($query)
            ->addColumn('nama_obat', function($row) {
                return $row->obat ? $row->obat->nama : '-';
            })
            ->editColumn('jumlah', function($row) {
                return $row->jumlah;
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
