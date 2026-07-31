<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Permintaan;
use App\Models\ERM\PermintaanItem;
use App\Models\ERM\Obat;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\KartuStok;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Principal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermintaanController extends Controller
{
    public function edit($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.permintaan.create', compact('permintaan', 'obats', 'pemasoks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'request_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.principal_id' => 'nullable|exists:erm_principals,id',
            'items.*.jumlah_box' => 'required|integer|min:0',
            'items.*.qty_total' => 'required|integer|min:1',
        ]);
        DB::transaction(function () use ($request, $id) {
            $permintaan = Permintaan::findOrFail($id);
            $permintaan->update([
                'request_date' => $request->request_date,
            ]);
            // Delete old items
            PermintaanItem::where('permintaan_id', $permintaan->id)->delete();
            // Insert new items
            foreach ($request->items as $item) {
                PermintaanItem::create([
                    'permintaan_id' => $permintaan->id,
                    'obat_id' => $item['obat_id'],
                    'pemasok_id' => $item['pemasok_id'],
                    'principal_id' => $item['principal_id'] ?? null,
                    'jumlah_box' => $item['jumlah_box'],
                    'qty_total' => $item['qty_total'],
                ]);
            }
        });
        return response()->json(['success' => true, 'message' => 'Permintaan updated!']);
    }
    public function index()
    {
        // Just return the view, DataTables will fetch data via AJAX
        $principals = Principal::orderBy('nama')->get(['id', 'nama']);

        return view('erm.permintaan.index', compact('principals'));
    }

    // DataTables AJAX endpoint
    public function data(Request $request)
    {
        $total = Permintaan::count();
        $query = Permintaan::with(['items.obat.principals', 'items.pemasok', 'items.principal', 'items.fakturBeliItems'])->orderBy('created_at', 'desc');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value');
        $statusFilter = $request->input('status');
        $principalFilter = $request->input('principal_id');

        $rangeInput = $request->input('request_date_range');
        if (!empty($rangeInput)) {
            $range = explode(' - ', $rangeInput);
            if (count($range) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', trim($range[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', trim($range[1]))->endOfDay();
                    $query->whereBetween('request_date', [$startDate->toDateString(), $endDate->toDateString()]);
                } catch (\Exception $e) {
                    // Ignore invalid range and continue without custom filter.
                }
            }
        } else {
            $query->whereBetween('request_date', [
                Carbon::now()->subMonthsNoOverflow(3)->toDateString(),
                Carbon::now()->toDateString(),
            ]);
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($principalFilter)) {
            $query->whereHas('items', function ($itemQuery) use ($principalFilter) {
                $itemQuery->where(function ($principalQuery) use ($principalFilter) {
                    $principalQuery->where('principal_id', $principalFilter)
                        ->orWhere(function ($fallbackQuery) use ($principalFilter) {
                            $fallbackQuery->whereNull('principal_id')
                                ->whereHas('obat.principals', function ($obatPrincipalQuery) use ($principalFilter) {
                                    $obatPrincipalQuery->where('erm_principals.id', $principalFilter);
                                });
                        });
                });
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('no_permintaan', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('request_date', 'like', "%$search%")
                  ->orWhereHas('items.pemasok', function($subq) use ($search) {
                      $subq->where('nama', 'like', "%$search%");
                  })
                  ->orWhereHas('items.obat', function($subq) use ($search) {
                      $subq->where('nama', 'like', "%$search%");
                  });
            });
            
            // Log search for debugging
            Log::info('Permintaan search query', [
                'search_term' => $search,
                'query_count' => $query->count()
            ]);
        }
        $filtered = $query->count();
        $filteredPermintaans = (clone $query)->get()->values();
        $permintaans = $query->skip($start)->take($length)->get()->values();
        $legacyFakturItemsByPermintaan = $this->buildLegacyFakturItemsMap($permintaans);

        $masterFakturs = $this->buildMasterFakturMap($filteredPermintaans);
        $filteredTotalNilaiPembelian = $this->sumPermintaanPurchaseValues($filteredPermintaans, $masterFakturs);

        $obatIds = $permintaans->flatMap(function ($permintaan) {
            return $permintaan->items->pluck('obat_id');
        })->filter()->unique()->values();

        $pemasokIds = $permintaans->flatMap(function ($permintaan) {
            return $permintaan->items->pluck('pemasok_id');
        })->filter()->unique()->values();

        $data = $permintaans->map(function($p, $i) use ($start, $masterFakturs, $legacyFakturItemsByPermintaan) {
            $aksi = '';
            $totalNilaiPembelian = $this->calculatePermintaanPurchaseValue($p, $masterFakturs);

            $obatList = $p->items->map(function($item) {
                $obatName = optional($item->obat)->nama ?? '-';
                $principalName = optional($item->principal)->nama
                    ?? optional(optional($item->obat)->principals->first())->nama
                    ?? null;
                $jenisObat = optional($item->obat)->is_generik ? 'Generik' : 'Paten';
                $principalBadge = $principalName
                    ? '<span class="badge badge-info mr-1">' . e($principalName) . '</span>'
                    : '';
                $jenisBadgeClass = $jenisObat === 'Generik' ? 'badge-success' : 'badge-primary';
                $jenisBadge = '<span class="badge ' . $jenisBadgeClass . '">' . e($jenisObat) . '</span>';

                return '<div class="permintaan-stack-row">'
                    . '<div class="font-weight-medium">' . e($obatName) . '</div>'
                    . '<div class="mt-1">' . $principalBadge . $jenisBadge . '</div>'
                    . '</div>';
            })->implode('');

            $qtyDimintaList = $p->items->map(function($item) {
                return '<div class="permintaan-stack-row text-right">'
                    . '<span class="font-weight-medium">' . e(number_format((float) $item->qty_total, 0, ',', '.')) . '</span>'
                    . '</div>';
            })->implode('');

            $qtyTerpenuhiList = $p->items->map(function($item) use ($p, $legacyFakturItemsByPermintaan) {
                $terpenuhi = $this->calculatePermintaanItemTerpenuhi($p, $item, $legacyFakturItemsByPermintaan);
                $qtyDiminta = (float) ($item->qty_total ?? 0);
                $terpenuhiClass = $terpenuhi < $qtyDiminta ? 'text-danger' : 'text-success';

                return '<div class="permintaan-stack-row text-right">'
                    . '<span class="font-weight-medium ' . $terpenuhiClass . '">' . e(number_format($terpenuhi, 0, ',', '.')) . '</span>'
                    . '</div>';
            })->implode('');

            if ($obatList === '') {
                $obatList = '-';
            }

            if ($qtyDimintaList === '') {
                $qtyDimintaList = '-';
            }

            if ($qtyTerpenuhiList === '') {
                $qtyTerpenuhiList = '-';
            }

            // Get pemasok name (should be the same for all items in this permintaan)
            $pemasokName = optional($p->items->first() ? $p->items->first()->pemasok : null)->nama ?? '-';
            // Get approved_by user name if approved
            $approved_by_name = null;
            if ($p->status === 'approved' && $p->approved_by) {
                $user = \App\Models\User::query()->find($p->approved_by);
                $approved_by_name = $user ? $user->name : null;
            }
            if ($p->status === 'approved') {
                $aksi .= '<div class="btn-group btn-group-sm mr-1" role="group">'
                    . '<a href="/erm/permintaan/'.$p->id.'/print" target="_blank" class="btn btn-secondary"><i class="fa fa-print"></i> Print</a>'
                    . '<a href="/erm/permintaan/'.$p->id.'/print-surat-pemesanan" target="_blank" class="btn btn-primary"><i class="fa fa-print"></i> Surat Pemesanan</a>'
                    . '</div>';
            }
            if ($p->status === 'waiting_approval') {
                $aksi .= '<div class="btn-group btn-group-sm mr-1" role="group">'
                    . '<a href="/erm/permintaan/'.$p->id.'/edit" class="btn btn-info">Edit</a>'
                    . '<button class="btn btn-success btn-approve" data-id="'.$p->id.'">Approve</button>'
                    . '<button class="btn btn-warning btn-reject" data-id="'.$p->id.'">Reject</button>'
                    . '<button class="btn btn-danger btn-delete" data-id="'.$p->id.'">Hapus</button>'
                    . '</div>';
            }
            if ($p->status === 'rejected') {
                $aksi .= '<div class="btn-group btn-group-sm mr-1" role="group">'
                    . '<button class="btn btn-danger btn-delete" data-id="'.$p->id.'">Hapus</button>'
                    . '</div>';
            }
            return [
                'id' => $p->id,
                'no' => $start + $i + 1,
                'no_permintaan' => '<div class="permintaan-request-cell">'
                    . '<div class="font-weight-medium">' . e($p->no_permintaan) . '</div>'
                    . '<small class="text-muted">' . e($pemasokName) . '</small>'
                    . '</div>',
                'obats' => $obatList,
                'qty_diminta' => $qtyDimintaList,
                'qty_terpenuhi' => $qtyTerpenuhiList,
                'request_date' => $p->request_date,
                'total_nilai_pembelian' => $totalNilaiPembelian,
                'status' => $p->status,
                'approved_by_name' => $approved_by_name,
                'jumlah_item' => $p->items->count(),
                'aksi' => $aksi,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
            'total_nilai_pembelian_filtered' => $filteredTotalNilaiPembelian,
        ]);
    }

    protected function buildMasterFakturMap($permintaans)
    {
        $obatIds = collect($permintaans)->flatMap(function ($permintaan) {
            return collect($permintaan->items)->pluck('obat_id');
        })->filter()->unique()->values();

        $pemasokIds = collect($permintaans)->flatMap(function ($permintaan) {
            return collect($permintaan->items)->pluck('pemasok_id');
        })->filter()->unique()->values();

        if ($obatIds->isEmpty() || $pemasokIds->isEmpty()) {
            return collect();
        }

        return MasterFaktur::query()
            ->whereIn('obat_id', $obatIds)
            ->whereIn('pemasok_id', $pemasokIds)
            ->get(['obat_id', 'pemasok_id', 'harga', 'diskon', 'diskon_type'])
            ->keyBy(function ($master) {
                return $master->obat_id . '|' . $master->pemasok_id;
            });
    }

    protected function calculatePermintaanPurchaseValue($permintaan, $masterFakturs)
    {
        return (float) collect($permintaan->items)->sum(function ($item) use ($masterFakturs) {
            return $this->calculateItemPurchaseValue($item, $masterFakturs);
        });
    }

    protected function sumPermintaanPurchaseValues($permintaans, $masterFakturs)
    {
        return (float) collect($permintaans)->sum(function ($permintaan) use ($masterFakturs) {
            return $this->calculatePermintaanPurchaseValue($permintaan, $masterFakturs);
        });
    }

    protected function calculateItemPurchaseValue($item, $masterFakturs)
    {
        $masterKey = $item->obat_id . '|' . $item->pemasok_id;
        $master = $masterFakturs->get($masterKey);
        $harga = (float) ($master->harga ?? 0);
        $diskon = (float) ($master->diskon ?? 0);
        $diskonType = strtolower(trim((string) ($master->diskon_type ?? 'nominal')));
        $qty = (float) ($item->qty_total ?? 0);
        $subtotal = $harga * $qty;
        $diskonValue = in_array($diskonType, ['persen', 'percent', '%', 'pct', 'pc', 'per'])
            ? ($subtotal * $diskon / 100)
            : $diskon;
        $setelahDiskon = max($subtotal - $diskonValue, 0);

        return $setelahDiskon + ($setelahDiskon * 11 / 100);
    }

    protected function buildLegacyFakturItemsMap($permintaans)
    {
        $noPermintaans = collect($permintaans)
            ->pluck('no_permintaan')
            ->filter()
            ->unique()
            ->values();

        if ($noPermintaans->isEmpty()) {
            return collect();
        }

        return FakturBeli::query()
            ->with('items')
            ->whereIn('no_permintaan', $noPermintaans)
            ->get()
            ->groupBy('no_permintaan')
            ->map(function ($fakturs) {
                return $fakturs->flatMap(function ($faktur) {
                    return collect($faktur->items)->map(function ($item) use ($faktur) {
                        return [
                            'obat_id' => $item->obat_id,
                            'principal_id' => $item->principal_id,
                            'pemasok_id' => $faktur->pemasok_id,
                            'qty' => (float) ($item->qty ?? 0),
                            'permintaan_item_id' => $item->permintaan_item_id,
                        ];
                    });
                })->values();
            });
    }

    protected function calculatePermintaanItemTerpenuhi($permintaan, $item, $legacyFakturItemsByPermintaan)
    {
        $linkedFakturItems = collect($item->fakturBeliItems);
        if ($linkedFakturItems->isNotEmpty()) {
            return (float) $linkedFakturItems->sum(function ($fakturItem) {
                return (float) ($fakturItem->qty ?? 0);
            });
        }

        $legacyItems = collect($legacyFakturItemsByPermintaan->get($permintaan->no_permintaan, []))
            ->filter(function ($legacyItem) use ($item) {
                if ((int) ($legacyItem['obat_id'] ?? 0) !== (int) ($item->obat_id ?? 0)) {
                    return false;
                }

                if ((int) ($legacyItem['pemasok_id'] ?? 0) !== (int) ($item->pemasok_id ?? 0)) {
                    return false;
                }

                if (!empty($item->principal_id) && !empty($legacyItem['principal_id'])) {
                    return (int) $legacyItem['principal_id'] === (int) $item->principal_id;
                }

                return true;
            })
            ->values();

        if ($legacyItems->isNotEmpty()) {
            return (float) $legacyItems->sum('qty');
        }

        return 0.0;
    }

    public function create()
    {
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.permintaan.create', compact('obats', 'pemasoks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.jumlah_box' => 'required|integer|min:0',
            'items.*.qty_total' => 'required|integer|min:1',
        ]);


        DB::transaction(function () use ($request) {
            if ($request->has('id') && $request->id) {
                // Update existing (keep as is)
                $permintaan = Permintaan::findOrFail($request->id);
                $permintaan->update([
                    'request_date' => $request->request_date,
                ]);
                PermintaanItem::where('permintaan_id', $permintaan->id)->delete();
                foreach ($request->items as $item) {
                    PermintaanItem::create([
                        'permintaan_id' => $permintaan->id,
                        'obat_id' => $item['obat_id'],
                        'pemasok_id' => $item['pemasok_id'],
                        'principal_id' => $item['principal_id'] ?? null,
                        'jumlah_box' => $item['jumlah_box'],
                        'qty_total' => $item['qty_total'],
                    ]);
                }
            } else {
                // Group items by pemasok_id
                $grouped = collect($request->items)->groupBy('pemasok_id');
                foreach ($grouped as $pemasok_id => $items) {
                    $no_permintaan = $this->generateNoPermintaan();
                    $permintaan = Permintaan::create([
                        'no_permintaan' => $no_permintaan,
                        'request_date' => $request->request_date,
                        'status' => 'waiting_approval',
                    ]);
                    foreach ($items as $item) {
                        PermintaanItem::create([
                            'permintaan_id' => $permintaan->id,
                            'obat_id' => $item['obat_id'],
                            'pemasok_id' => $item['pemasok_id'],
                            'principal_id' => $item['principal_id'] ?? null,
                            'jumlah_box' => $item['jumlah_box'],
                            'qty_total' => $item['qty_total'],
                        ]);
                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Permintaan saved & grouped by pemasok!']);

    }

    /**
     * Generate unique no_permintaan in format: PRYYYYMMDD-XXX
     * Example: PR20230809-001
     */
    protected function generateNoPermintaan()
    {
        $date = date('Ymd');
        $prefix = 'PR' . $date . '-';
        $last = \App\Models\ERM\Permintaan::where('no_permintaan', 'like', $prefix . '%')
            ->orderByDesc('no_permintaan')
            ->first();
        if ($last && preg_match('/-(\d{3})$/', $last->no_permintaan, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

        public function getMasterFaktur(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
        ]);
        $master = \App\Models\ERM\MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->first();
        if (!$master) {
            return response()->json(['found' => false]);
        }
        return response()->json([
            'found' => true,
            'harga' => $master->harga,
            'qty_per_box' => $master->qty_per_box,
            'diskon' => $master->diskon,
            'diskon_type' => $master->diskon_type,
            'principal_id' => $master->principal_id,
            'principal_nama' => $master->principal ? $master->principal->nama : null,
        ]);
    }

    public function forecastPreview(Request $request)
    {
        $request->validate([
            'period_months' => 'nullable|integer|in:1,3,6,12',
            'obat_ids' => 'nullable|array',
            'obat_ids.*' => 'integer|exists:erm_obat,id',
        ]);

        $periodMonths = (int) $request->input('period_months', 3);
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;
        $obatIds = collect($request->input('obat_ids', []))
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        $periodEnd = Carbon::now()->startOfMonth()->subDay()->endOfDay();
        $periodStart = (clone $periodEnd)->subMonthsNoOverflow($periodMonths - 1)->startOfMonth()->startOfDay();

        if ($obatIds->isEmpty()) {
            return response()->json([
                'period_months' => $periodMonths,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'rows' => [],
            ]);
        }

        $keluarPerObat = KartuStok::query()
            ->select('obat_id', DB::raw('SUM(qty) as total_keluar'))
            ->whereIn('obat_id', $obatIds)
            ->where('tipe', 'keluar')
            ->where('ref_type', 'invoice_penjualan')
            ->whereBetween('tanggal', [$periodStart, $periodEnd])
            ->groupBy('obat_id')
            ->pluck('total_keluar', 'obat_id');

        $stockPerObat = ObatStokGudang::query()
            ->whereHas('gudang', function ($query) {
                $query->where('nama', '!=', 'Gudang ED');
            })
            ->whereIn('obat_id', $obatIds)
            ->select('obat_id', DB::raw('SUM(stok) as total_stock'))
            ->groupBy('obat_id')
            ->pluck('total_stock', 'obat_id');

        $rows = Obat::withInactive()
            ->whereIn('id', $obatIds)
            ->get(['id', 'nama'])
            ->map(function ($obat) use ($keluarPerObat, $stockPerObat, $periodMonths) {
                $obatKeluar = (float) ($keluarPerObat[$obat->id] ?? 0);
                $averageMonthlyKeluar = $periodMonths > 0 ? ceil($obatKeluar / $periodMonths) : 0;

                return [
                    'obat_id' => $obat->id,
                    'obat_nama' => $obat->nama,
                    'total_stock' => round((float) ($stockPerObat[$obat->id] ?? 0), 2),
                    'obat_keluar' => round($obatKeluar, 2),
                    'average_monthly_keluar' => $averageMonthlyKeluar,
                ];
            })
            ->values();

        return response()->json([
            'period_months' => $periodMonths,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

        public function approve($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);
        if ($permintaan->status !== 'waiting_approval') {
            return redirect()->back()->with('error', 'Permintaan sudah diproses.');
        }
        $userId = Auth::id();
        $now = now();
        DB::transaction(function () use ($permintaan, $userId, $now) {
            // Group items by pemasok
            $grouped = collect($permintaan->items)->groupBy('pemasok_id');
            foreach ($grouped as $pemasokId => $items) {
                $faktur = \App\Models\ERM\FakturBeli::create([
                    'permintaan_id' => $permintaan->id,
                    'pemasok_id' => $pemasokId,
                    'no_faktur' => null,
                    'no_permintaan' => $permintaan->no_permintaan,
                    'requested_date' => $permintaan->request_date,
                    'status' => 'diminta',
                ]);
                foreach ($items as $item) {
                    // Get harga, diskon, diskon_type from master faktur
                    $master = \App\Models\ERM\MasterFaktur::where('obat_id', $item->obat_id)
                        ->where('pemasok_id', $item->pemasok_id)
                        ->first();
                        $harga = $master ? $master->harga : 0;
                        $diskon = $master ? $master->diskon : 0;
                        $diskon_type = $master ? ($master->diskon_type == 'percent' ? 'percent' : $master->diskon_type) : 'nominal';
                        \App\Models\ERM\FakturBeliItem::create([
                            'fakturbeli_id' => $faktur->id,
                            'permintaan_item_id' => $item->id,
                            'obat_id' => $item->obat_id,
                            'principal_id' => $item->principal_id ?? null,
                            'qty' => 0,
                            'sisa' => $item->qty_total,
                            'harga' => $harga,
                            'diskon' => $diskon,
                            'diskon_type' => $diskon_type,
                            'diminta' => $item->qty_total,
                        ]);
                }
            }
            $permintaan->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_date' => $now,
            ]);
        });
        return redirect()->route('erm.permintaan.index')->with('success', 'Permintaan disetujui dan faktur berhasil dibuat!');
    }

    public function reject($id)
    {
        $permintaan = Permintaan::findOrFail($id);

        if ($permintaan->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'Hanya permintaan yang menunggu approval yang bisa direject.'], 422);
        }

        $permintaan->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_date' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Permintaan berhasil direject.']);
    }

    public function nilaiPembelian($id)
    {
        $permintaan = Permintaan::with(['items.obat.principals', 'items.pemasok', 'items.principal'])->findOrFail($id);
        $ppnRate = 11;

        $details = collect($permintaan->items)->map(function ($item) use ($ppnRate) {
            $master = MasterFaktur::query()
                ->with('principal')
                ->where('obat_id', $item->obat_id)
                ->where('pemasok_id', $item->pemasok_id)
                ->first();

            $harga = (float) ($master->harga ?? 0);
            $qty = (float) ($item->qty_total ?? 0);
            $box = (float) ($item->jumlah_box ?? 0);
            $diskon = (float) ($master->diskon ?? 0);
            $diskonType = strtolower(trim((string) ($master->diskon_type ?? 'nominal')));
            $subtotal = $harga * $qty;
            $diskonValue = in_array($diskonType, ['persen', 'percent', '%', 'pct', 'pc', 'per'])
                ? ($subtotal * $diskon / 100)
                : $diskon;
            $setelahDiskon = max($subtotal - $diskonValue, 0);
            $ppnValue = $setelahDiskon * $ppnRate / 100;
            $totalHarga = $setelahDiskon + $ppnValue;
            $principalName = optional($item->principal)->nama
                ?? optional($master?->principal)->nama
                ?? optional(optional($item->obat)->principals->first())->nama
                ?? '-';
            $jenisObat = optional($item->obat)->is_generik ? 'Generik' : 'Paten';

            return [
                'nama_obat' => optional($item->obat)->nama ?? '-',
                'principal_name' => $principalName,
                'jenis_obat' => $jenisObat,
                'qty' => $qty,
                'box' => $box,
                'harga' => $harga,
                'diskon' => $diskon,
                'diskon_type' => $diskonType,
                'subtotal' => $subtotal,
                'setelah_diskon' => $setelahDiskon,
                'ppn_rate' => $ppnRate,
                'ppn' => $ppnValue,
                'total_harga' => $totalHarga,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'no_permintaan' => $permintaan->no_permintaan,
            'request_date' => $permintaan->request_date,
            'items' => $details,
            'summary' => [
                'subtotal' => (float) $details->sum('subtotal'),
                'setelah_diskon' => (float) $details->sum('setelah_diskon'),
                'ppn' => (float) $details->sum('ppn'),
                'total_harga' => (float) $details->sum('total_harga'),
            ],
        ]);
    }

    public function destroy($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);

        if ($permintaan->status === 'approved') {
            return response()->json(['success' => false, 'message' => 'Permintaan yang sudah approved tidak bisa dihapus.'], 422);
        }

        DB::transaction(function () use ($permintaan) {
            $permintaan->items()->delete();
            $permintaan->delete();
        });

        return response()->json(['success' => true, 'message' => 'Permintaan berhasil dihapus.']);
    }

    
// ...existing code...

    /**
     * Print Surat Permintaan as PDF using mPDF
     */
    public function printSuratPermintaan($id)
    {
        $permintaan = \App\Models\ERM\Permintaan::with(['items', 'items.obat', 'items.pemasok'])->findOrFail($id);
    $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L']);
        $html = view('erm.permintaan.print', compact('permintaan'))->render();
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SuratPermintaan-'.$permintaan->no_permintaan.'.pdf"'
        ]);
    }

    public function printSuratPemesanan($id)
    {
        $permintaan = Permintaan::findOrFail($id);

        if ($permintaan->status !== 'approved') {
            abort(403, 'Surat pemesanan hanya tersedia untuk permintaan yang sudah approved.');
        }

        $rowsPerPage = 12;
        $items = $permintaan->items()->with(['obat', 'pemasok'])->get()->values();

        $printItems = $items->map(function ($item) {
            return [
                'nama_obat' => optional($item->obat)->nama ?? '-',
                'jumlah' => rtrim(rtrim(number_format((float) $item->jumlah_box, 2, ',', '.'), '0'), ','),
            ];
        });

        $pages = $printItems->chunk($rowsPerPage)->map(function ($chunk) use ($rowsPerPage) {
            return $chunk->pad($rowsPerPage, null)->values();
        });

        if ($pages->isEmpty()) {
            $pages = collect([collect()->pad($rowsPerPage, null)->values()]);
        }

        $pemasokName = optional(optional($items->first())->pemasok)->nama ?? '-';
        $requestDate = $permintaan->request_date
            ? Carbon::parse($permintaan->request_date)->translatedFormat('d/m/Y')
            : '-';

        return view('erm.permintaan.print-surat-pemesanan', [
            'permintaan' => $permintaan,
            'pages' => $pages,
            'pemasokName' => $pemasokName,
            'requestDate' => $requestDate,
            'rowsPerPage' => $rowsPerPage,
        ]);
    }
}
