<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Yajra\DataTables\Facades\DataTables;

class RawatJalanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            return datatables()->of($visitations)
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal', fn($v) => $v->tanggal_visitation)
                ->addColumn('status', fn($v) => ucfirst($v->status))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-') // tambahkan ini
                ->addColumn('dokumen', function ($v) {
                    $url = route('erm.asesmen.create', $v->id);
                    return '<a href="' . $url . '" class="btn btn-sm btn-primary">Lihat</a>';
                })
                ->rawColumns(['dokumen'])
                ->make(true);
        }

        return view('erm.rawatjalans.index');
    }
}
