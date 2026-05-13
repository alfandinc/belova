<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Principal;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            $filteredData = $this->applyPurchaseSummarySearch($data, $request->input('search.value'));

            $totalNominalAll = $filteredData->sum(function ($row) {
                return $row['total_nominal'] ?? 0;
            });

            return DataTables::of($data)
                ->addColumn('total_nominal_formatted', function ($row) {
                    return 'Rp ' . number_format($row['total_nominal'], 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    if (array_key_exists('has_detail', $row) && !$row['has_detail']) {
                        return '<span class="text-muted">-</span>';
                    }

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
        $purchaseItems = FakturBeliItem::with(['principal', 'obat.principals', 'fakturbeli'])
            ->whereHas('fakturbeli', function ($query) use ($startDate, $endDate) {
                $query->where('status', '!=', 'diretur')
                    ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('received_date', [$startDate, $endDate]);
                    });
            })
            ->get();

        $masterFakturMap = $this->buildMasterFakturPrincipalMap($purchaseItems);
        $principalItems = $purchaseItems
            ->map(function ($item) use ($masterFakturMap) {
                $resolvedPrincipal = $this->resolvePrincipalForItem($item, $masterFakturMap);

                if (!$resolvedPrincipal) {
                    return [
                        'principal_id' => '__unmapped__',
                        'principal_name' => 'Principal tidak terpetakan',
                        'principal_model' => null,
                        'has_detail' => false,
                        'item' => $item,
                    ];
                }

                return [
                    'principal_id' => $resolvedPrincipal->id,
                    'principal_name' => $resolvedPrincipal->nama,
                    'principal_model' => $resolvedPrincipal,
                    'has_detail' => true,
                    'item' => $item,
                ];
            })
            ->values();

        return $principalItems
            ->groupBy('principal_id')
            ->map(function ($principalItems, $principalId) {
                $firstEntry = $principalItems->first();
                $principal = $firstEntry['principal_model'] ?? null;
                $principalName = $firstEntry['principal_name'] ?? 'Principal tidak terpetakan';
                $hasDetail = $firstEntry['has_detail'] ?? false;
                $lastPurchase = $principalItems
                    ->map(function ($entry) {
                        return optional($entry['item']->fakturbeli)->received_date;
                    })
                    ->filter()
                    ->sortDesc()
                    ->first() ?? '-';

                $uniqueItems = $principalItems
                    ->groupBy(function ($entry) {
                        return $entry['item']->obat_id;
                    })
                    ->map(function ($obatItems) use ($principal, $principalName) {
                        $latestEntry = $obatItems->sortByDesc(function ($entry) {
                            return optional($entry['item']->fakturbeli)->received_date ?? '';
                        })->first() ?? $obatItems->first();
                        $latestItem = $latestEntry['item'];

                        return [
                            'obat_id' => $latestItem->obat_id,
                            'nama_obat' => $latestItem->obat->nama ?? 'Unknown',
                            'total_qty' => $obatItems->sum(function ($entry) {
                                return $entry['item']->qty ?? 0;
                            }),
                            'last_price' => $latestItem->harga,
                            'principal_name' => $principalName,
                        ];
                    })
                    ->values();

                return [
                    'id' => $hasDetail ? (int) $principalId : $principalId,
                    'nama_pemasok' => $principalName,
                    'alamat' => $principal->alamat ?? null,
                    'telepon' => $principal->telepon ?? null,
                    'email' => $principal->email ?? null,
                    'entity_type' => 'principal',
                    'has_detail' => $hasDetail,
                    'total_nominal' => $principalItems->sum(function ($entry) {
                        $item = $entry['item'];

                        if (!is_null($item->total_amount)) {
                            return (float) $item->total_amount;
                        }

                        return (float) ($item->qty ?? 0) * (float) ($item->harga ?? 0);
                    }),
                    'pembelian_terakhir' => $lastPurchase,
                    'qty_jenis_item' => $uniqueItems->count(),
                    'jumlah_faktur' => $principalItems->pluck('item.fakturbeli_id')->filter()->unique()->count(),
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

        $purchaseItems = FakturBeliItem::with(['principal', 'obat.principals', 'fakturbeli.pemasok'])
            ->whereHas('fakturbeli', function ($query) {
                $query->where('status', '!=', 'diretur');
            })
            ->get();

        $masterFakturMap = $this->buildMasterFakturPrincipalMap($purchaseItems);

        $purchaseHistory = $purchaseItems
            ->filter(function ($item) use ($principal, $masterFakturMap) {
                $resolvedPrincipal = $this->resolvePrincipalForItem($item, $masterFakturMap);

                return $resolvedPrincipal && (int) $resolvedPrincipal->id === (int) $principal->id;
            })
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

    private function buildMasterFakturPrincipalMap(Collection $purchaseItems): Collection
    {
        $obatIds = $purchaseItems->pluck('obat_id')->filter()->unique()->values();
        $pemasokIds = $purchaseItems
            ->map(function ($item) {
                return optional($item->fakturbeli)->pemasok_id;
            })
            ->filter()
            ->unique()
            ->values();

        if ($obatIds->isEmpty() || $pemasokIds->isEmpty()) {
            return collect();
        }

        return MasterFaktur::with('principal')
            ->whereIn('obat_id', $obatIds)
            ->whereIn('pemasok_id', $pemasokIds)
            ->get()
            ->keyBy(function ($masterFaktur) {
                return $masterFaktur->obat_id . ':' . $masterFaktur->pemasok_id;
            });
    }

    private function resolvePrincipalForItem(FakturBeliItem $item, Collection $masterFakturMap): ?Principal
    {
        if ($item->principal) {
            return $item->principal;
        }

        $pemasokId = optional($item->fakturbeli)->pemasok_id;
        if ($item->obat_id && $pemasokId) {
            $masterFaktur = $masterFakturMap->get($item->obat_id . ':' . $pemasokId);
            if ($masterFaktur && $masterFaktur->principal) {
                return $masterFaktur->principal;
            }
        }

        $obatPrincipals = optional($item->obat)->principals;
        if ($obatPrincipals instanceof Collection && $obatPrincipals->count() === 1) {
            return $obatPrincipals->first();
        }

        return null;
    }

    private function applyPurchaseSummarySearch(Collection $data, ?string $searchValue): Collection
    {
        $searchValue = trim((string) $searchValue);
        if ($searchValue === '') {
            return $data;
        }

        $searchValue = mb_strtolower($searchValue);

        return $data->filter(function ($row) use ($searchValue) {
            $searchableValues = [
                $row['nama_pemasok'] ?? '',
                $row['alamat'] ?? '',
                $row['telepon'] ?? '',
                $row['email'] ?? '',
                $row['pembelian_terakhir'] ?? '',
                (string) ($row['qty_jenis_item'] ?? ''),
                (string) ($row['jumlah_faktur'] ?? ''),
                (string) ($row['total_nominal'] ?? ''),
            ];

            foreach ($searchableValues as $value) {
                if (str_contains(mb_strtolower((string) $value), $searchValue)) {
                    return true;
                }
            }

            return false;
        })->values();
    }
}