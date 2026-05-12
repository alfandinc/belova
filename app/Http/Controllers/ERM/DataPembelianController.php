<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Principal;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DataPembelianController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $groupBy = $request->input('group_by', 'pemasok') === 'principal' ? 'principal' : 'pemasok';
            $data = $groupBy === 'principal'
                ? $this->getPrincipalPurchaseSummary($startDate, $endDate)
                : $this->getPemasokPurchaseSummary($startDate, $endDate);

            $totalNominalAll = $data->sum(function ($row) {
                return $row['total_nominal'] ?? 0;
            });

            return DataTables::of($data)
                ->addColumn('total_nominal_formatted', function ($row) {
                    return 'Rp ' . number_format($row['total_nominal'], 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    $detailRoute = ($row['entity_type'] ?? 'pemasok') === 'principal'
                        ? 'erm.datapembelian.detailPrincipal'
                        : 'erm.datapembelian.detail';
                    $detailUrl = route($detailRoute, $row['id']);

                    return '<a href="' . $detailUrl . '" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> Detail
                            </a>';
                })
                ->rawColumns(['action'])
                ->with([
                    'total_nominal_all' => 'Rp ' . number_format($totalNominalAll, 0, ',', '.'),
                    'group_by' => $groupBy,
                ])
                ->make(true);
        }

        return view('erm.datapembelian.index');
    }

    private function getPemasokPurchaseSummary($startDate, $endDate)
    {
        return Pemasok::select('erm_pemasok.*')
            ->leftJoin('erm_fakturbeli', 'erm_pemasok.id', '=', 'erm_fakturbeli.pemasok_id')
            ->with(['fakturBeli' => function ($query) use ($startDate, $endDate) {
                $query->where('status', '!=', 'diretur')
                    ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('received_date', [$startDate, $endDate]);
                    })
                    ->with(['items.obat', 'items.principal'])
                    ->orderBy('received_date', 'desc');
            }])
            ->groupBy('erm_pemasok.id')
            ->get()
            ->map(function ($pemasok) {
                $totalNominal = $pemasok->fakturBeli->sum('total');
                $lastPurchase = $pemasok->fakturBeli->first()?->received_date ?? '-';
                $allItems = $pemasok->fakturBeli->flatMap->items;

                $uniqueItems = $allItems
                    ->groupBy('obat_id')
                    ->map(function ($obatItems) {
                        $latestItem = $obatItems->first();

                        return [
                            'obat_id' => $latestItem->obat_id,
                            'nama_obat' => $latestItem->obat->nama ?? 'Unknown',
                            'total_qty' => $obatItems->sum('qty'),
                            'last_price' => $latestItem->harga,
                            'principal_name' => $latestItem->principal?->nama ?? null,
                        ];
                    })
                    ->values();

                return [
                    'id' => $pemasok->id,
                    'nama_pemasok' => $pemasok->nama,
                    'alamat' => $pemasok->alamat,
                    'telepon' => $pemasok->telepon,
                    'email' => $pemasok->email,
                    'entity_type' => 'pemasok',
                    'total_nominal' => $totalNominal,
                    'pembelian_terakhir' => $lastPurchase,
                    'qty_jenis_item' => $uniqueItems->count(),
                    'jumlah_faktur' => $pemasok->fakturBeli->count(),
                    'items_detail' => $uniqueItems->toArray(),
                ];
            });
    }

    private function getPrincipalPurchaseSummary($startDate, $endDate)
    {
        $purchaseItems = FakturBeliItem::with(['principal', 'obat', 'fakturbeli'])
            ->whereNotNull('principal_id')
            ->whereHas('fakturbeli', function ($query) use ($startDate, $endDate) {
                $query->where('status', '!=', 'diretur')
                    ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('received_date', [$startDate, $endDate]);
                    });
            })
            ->get();

        $principalIds = $purchaseItems->pluck('principal_id')->filter()->unique()->values();
        $principals = Principal::whereIn('id', $principalIds)->get()->keyBy('id');

        return $purchaseItems
            ->groupBy('principal_id')
            ->map(function ($principalItems, $principalId) use ($principals) {
                $principal = $principals->get($principalId);
                $lastPurchase = $principalItems
                    ->map(function ($item) {
                        return optional($item->fakturbeli)->received_date;
                    })
                    ->filter()
                    ->sortDesc()
                    ->first() ?? '-';

                $uniqueItems = $principalItems
                    ->groupBy('obat_id')
                    ->map(function ($obatItems) use ($principal) {
                        $latestItem = $obatItems->sortByDesc(function ($item) {
                            return optional($item->fakturbeli)->received_date ?? '';
                        })->first() ?? $obatItems->first();

                        return [
                            'obat_id' => $latestItem->obat_id,
                            'nama_obat' => $latestItem->obat->nama ?? 'Unknown',
                            'total_qty' => $obatItems->sum('qty'),
                            'last_price' => $latestItem->harga,
                            'principal_name' => $principal->nama ?? null,
                        ];
                    })
                    ->values();

                return [
                    'id' => (int) $principalId,
                    'nama_pemasok' => $principal->nama ?? 'Tanpa Principal',
                    'alamat' => $principal->alamat ?? null,
                    'telepon' => $principal->telepon ?? null,
                    'email' => $principal->email ?? null,
                    'entity_type' => 'principal',
                    'total_nominal' => $principalItems->sum(function ($item) {
                        if (!is_null($item->total_amount)) {
                            return (float) $item->total_amount;
                        }

                        return (float) ($item->qty ?? 0) * (float) ($item->harga ?? 0);
                    }),
                    'pembelian_terakhir' => $lastPurchase,
                    'qty_jenis_item' => $uniqueItems->count(),
                    'jumlah_faktur' => $principalItems->pluck('fakturbeli_id')->filter()->unique()->count(),
                    'items_detail' => $uniqueItems->toArray(),
                ];
            })
            ->values();
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

    public function detailPrincipal($id)
    {
        $principal = Principal::findOrFail($id);

        $purchaseItems = FakturBeliItem::with(['obat', 'fakturbeli.pemasok'])
            ->where('principal_id', $principal->id)
            ->whereHas('fakturbeli', function ($query) {
                $query->where('status', '!=', 'diretur');
            })
            ->get();

        $purchaseHistory = $purchaseItems
            ->groupBy('fakturbeli_id')
            ->map(function ($items) {
                $faktur = optional($items->first())->fakturbeli;

                return [
                    'no_faktur' => $faktur?->no_faktur ?: '-',
                    'received_date' => $faktur?->received_date,
                    'due_date' => $faktur?->due_date,
                    'pemasok_nama' => $faktur?->pemasok?->nama ?: '-',
                    'jumlah_item' => $items->pluck('obat_id')->filter()->unique()->count(),
                    'qty_total' => $items->sum('qty'),
                    'total_principal' => $items->sum(function ($item) {
                        if (!is_null($item->total_amount)) {
                            return (float) $item->total_amount;
                        }

                        return (float) ($item->qty ?? 0) * (float) ($item->harga ?? 0);
                    }),
                    'status' => $faktur?->status ?: '-',
                    'obat_ids' => $items->pluck('obat_id')->filter()->unique()->values()->implode(','),
                ];
            })
            ->sortByDesc('received_date')
            ->values();

        return view('erm.datapembelian.detail-principal', [
            'principal' => $principal,
            'purchaseHistory' => $purchaseHistory,
        ]);
    }
}