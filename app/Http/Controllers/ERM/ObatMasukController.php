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

        $query = FakturBeliItem::with(['obat', 'fakturbeli'])
            ->where('qty', '>', 0)
            ->whereHas('fakturbeli', function($q) use ($start, $end) {
                $q->where('status', 'diapprove');
                if ($start && $end) {
                    $q->whereBetween('received_date', [$start, $end]);
                }
            })
            ->orderByDesc('erm_fakturbeli_items.id');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('nama_obat', function ($item) {
                return $item->obat->nama ?? '-';
            })
            ->addColumn('no_faktur', function ($item) {
                return $item->fakturbeli->no_faktur ?? '-';
            })
            ->filterColumn('nama_obat', function($query, $keyword) {
                $query->whereHas('obat', function($q) use ($keyword) {
                    $q->where('nama', 'like', "%$keyword%");
                });
            })
            ->filterColumn('no_faktur', function($query, $keyword) {
                $query->whereHas('fakturbeli', function($q) use ($keyword) {
                    $q->where('no_faktur', 'like', "%$keyword%");
                });
            })
            ->orderColumn('nama_obat', function ($query, $order) {
                $query->join('erm_obat', 'erm_fakturbeli_items.obat_id', '=', 'erm_obat.id')
                      ->orderBy('erm_obat.nama', $order);
            })
            ->orderColumn('no_faktur', function ($query, $order) {
                $query->join('erm_fakturbeli', 'erm_fakturbeli_items.fakturbeli_id', '=', 'erm_fakturbeli.id')
                      ->orderBy('erm_fakturbeli.no_faktur', $order);
            })
            ->rawColumns(['nama_obat', 'qty', 'no_faktur'])
            ->make(true);
    }
}
