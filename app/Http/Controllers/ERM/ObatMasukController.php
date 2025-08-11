<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturBeliItem;

class ObatMasukController extends Controller
{
    public function index()
    {
    return view('erm.obat_masuk.index');
    }

    public function data()
    {
        $start = request('start');
        $end = request('end');

        $query = FakturBeliItem::selectRaw('obat_id, SUM(qty) as total_masuk')
            ->where('qty', '>', 0)
            ->whereHas('fakturbeli', function($q) use ($start, $end) {
                $q->where('status', 'diapprove');
                if ($start && $end) {
                    $q->whereBetween('received_date', [$start, $end]);
                }
            })
            ->groupBy('obat_id')
            ->with('obat');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('nama_obat', function ($item) {
                return $item->obat->nama ?? '-';
            })
            ->addColumn('qty', function ($item) {
                return $item->total_masuk;
            })
            ->addColumn('detail', function ($item) {
                return '<button class="btn btn-info btn-sm btn-detail" data-obat-id="'.$item->obat_id.'">Detail</button>';
            })
            ->rawColumns(['nama_obat', 'qty', 'detail'])
            ->make(true);

    }

    public function detail(Request $request)
    {
        $obatId = $request->input('obat_id');
        $start = $request->input('start');
        $end = $request->input('end');

        $items = FakturBeliItem::with('fakturbeli')
            ->where('obat_id', $obatId)
            ->where('qty', '>', 0)
            ->whereHas('fakturbeli', function($q) use ($start, $end) {
                $q->where('status', 'diapprove');
                if ($start && $end) {
                    $q->whereBetween('received_date', [$start, $end]);
                }
            })
            ->get();

        $html = '<div class="table-responsive"><table class="table table-bordered table-striped">';
        $html .= '<thead><tr><th>Faktur No</th><th>Jumlah Masuk</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . ($item->fakturbeli->no_faktur ?? '-') . '</td>';
            $html .= '<td>' . $item->qty . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}
