<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\Pemasok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DataPembelianController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            // Get supplier purchase data with aggregated information
            $data = Pemasok::select('erm_pemasok.*')
                ->leftJoin('erm_fakturbeli', 'erm_pemasok.id', '=', 'erm_fakturbeli.pemasok_id')
                ->with(['fakturBeli' => function($query) use ($startDate, $endDate) {
                    // eager-load items with obat and principal to include principal name
                    $query->where('status', '!=', 'diretur')
                          ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                              $q->whereBetween('received_date', [$startDate, $endDate]);
                          })
                          ->with(['items.obat', 'items.principal'])
                          ->orderBy('received_date', 'desc');
                }])
                ->groupBy('erm_pemasok.id')
                ->get()
                ->map(function($pemasok) {
                    // Calculate total purchases
                    $totalNominal = $pemasok->fakturBeli->sum('total');
                    
                    // Get last purchase date
                    $lastPurchase = $pemasok->fakturBeli->first()?->received_date ?? '-';
                    
                    // Get unique items with details
                    $uniqueItems = collect();
                    $obatIds = collect();
                    
                    foreach($pemasok->fakturBeli as $faktur) {
                        foreach($faktur->items as $item) {
                            if (!$obatIds->contains($item->obat_id)) {
                                $obatIds->push($item->obat_id);
                                $uniqueItems->push([
                                    'obat_id' => $item->obat_id,
                                    'nama_obat' => $item->obat->nama ?? 'Unknown',
                                    'total_qty' => $pemasok->fakturBeli->flatMap->items->where('obat_id', $item->obat_id)->sum('qty'),
                                    'last_price' => $item->harga,
                                    'principal_name' => $item->principal?->nama ?? null
                                ]);
                            }
                        }
                    }
                    
                    return [
                        'id' => $pemasok->id,
                        'nama_pemasok' => $pemasok->nama,
                        'alamat' => $pemasok->alamat,
                        'telepon' => $pemasok->telepon,
                        'email' => $pemasok->email,
                        'total_nominal' => $totalNominal,
                        'pembelian_terakhir' => $lastPurchase,
                        'qty_jenis_item' => $uniqueItems->count(),
                        'jumlah_faktur' => $pemasok->fakturBeli->count(),
                        'items_detail' => $uniqueItems->toArray()
                    ];
                });

            // compute total nominal for all rows in the current dataset (before pagination)
            $totalNominalAll = $data->sum(function ($row) {
                return $row['total_nominal'] ?? 0;
            });

            return DataTables::of($data)
                ->addColumn('total_nominal_formatted', function ($row) {
                    return 'Rp ' . number_format($row['total_nominal'], 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    $detailUrl = route('erm.datapembelian.detail', $row['id']);
                    return '<a href="' . $detailUrl . '" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> Detail
                            </a>';
                })
                ->rawColumns(['action'])
                ->with(['total_nominal_all' => 'Rp ' . number_format($totalNominalAll, 0, ',', '.')])
                ->make(true);
        }

        return view('erm.datapembelian.index');
    }

    public function detail($id)
    {
        $pemasok = Pemasok::with(['fakturBeli' => function($query) {
            $query->where('status', '!=', 'diretur')
                  ->with(['items.obat'])
                  ->orderBy('received_date', 'desc');
        }])->findOrFail($id);

        return view('erm.datapembelian.detail', compact('pemasok'));
    }
}