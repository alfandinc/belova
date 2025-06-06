<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BillingController extends Controller
{
    public function index()
    {
        $visitations = Visitation::with('pasien')->get();

        // dd($visitations);
        return view('finance.billing.index', compact('visitations'));
    }

    public function create(Request $request, $visitation_id)
    {
        if ($request->ajax()) {
            $billings = Billing::where('visitation_id', $visitation_id)->get();

            // First, extract racikan items to process them separately
            $racikanGroups = [];
            $regularBillings = [];

            foreach ($billings as $billing) {
                if (
                    $billing->billable_type == 'App\Models\ERM\ResepFarmasi' &&
                    $billing->billable->racikan_ke != null &&
                    $billing->billable->racikan_ke > 0
                ) {
                    $racikanKey = $billing->billable->racikan_ke;
                    if (!isset($racikanGroups[$racikanKey])) {
                        $racikanGroups[$racikanKey] = [];
                    }
                    $racikanGroups[$racikanKey][] = $billing;
                } else {
                    $regularBillings[] = $billing;
                }
            }

            // Create processed billing items (regular + consolidated racikan)
            $processedBillings = $regularBillings;

            // Process each racikan group
            foreach ($racikanGroups as $racikanKey => $racikanItems) {
                // Use the first item as base
                $firstItem = $racikanItems[0];

                // Calculate total price for the racikan
                $totalPrice = 0;
                $obatList = [];
                $bungkus = 0;

                foreach ($racikanItems as $item) {
                    $totalPrice += $item->jumlah;
                    $obatList[] = $item->billable->obat->nama ?? 'Obat Tidak Diketahui';
                    // Get bungkus from the first item only (should be same for all items in racikan)
                    if ($bungkus == 0) {
                        $bungkus = $item->billable->bungkus ?? 0;
                    }
                }

                // Clone the first item and modify its properties for display
                $racikanItem = clone $firstItem;
                $racikanItem->is_racikan = true;
                $racikanItem->racikan_obat_list = $obatList;
                $racikanItem->racikan_total_price = $totalPrice;
                $racikanItem->racikan_bungkus = $bungkus;

                $processedBillings[] = $racikanItem;
            }

            return DataTables::of($processedBillings)
                ->addIndexColumn()
                ->addColumn('nama_item', function ($row) {
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        return 'Obat Racikan';
                    } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                        return $row->billable->obat->nama ?? 'N/A';
                    } else {
                        return $row->billable->nama ?? '-';
                    }
                })
                ->addColumn('jumlah_raw', function ($row) {
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        return $row->racikan_total_price;
                    }
                    return $row->jumlah;
                })
                ->addColumn('diskon_raw', function ($row) {
                    return $row->diskon ?? '';
                })
                ->addColumn('jumlah', function ($row) {
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        return 'Rp ' . number_format($row->racikan_total_price, 0, ',', '.');
                    }
                    return 'Rp ' . number_format($row->jumlah, 0, ',', '.');
                })
                ->addColumn('harga_akhir', function ($row) {
                    // Initially, harga_akhir is the same as jumlah
                    // This will be modified on the client side when discounts are applied
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        return 'Rp ' . number_format($row->racikan_total_price, 0, ',', '.');
                    }

                    // Calculate the final price after discount
                    $finalPrice = $row->jumlah;
                    if ($row->diskon && $row->diskon > 0) {
                        if ($row->diskon_type == '%') {
                            $finalPrice = $finalPrice - ($finalPrice * ($row->diskon / 100));
                        } else {
                            $finalPrice = $finalPrice - $row->diskon;
                        }
                    }

                    return 'Rp ' . number_format($finalPrice, 0, ',', '.');
                })
                ->addColumn('qty', function ($row) {
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        return $row->racikan_bungkus;
                    } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                        return $row->billable->jumlah ?? 1;
                    }
                    return $row->billable->qty ?? 1;
                })
                ->addColumn('diskon', function ($row) {
                    if (!$row->diskon || $row->diskon == 0) {
                        return '-';
                    }

                    if ($row->diskon_type == '%') {
                        return $row->diskon . '%';
                    } else {
                        return 'Rp ' . number_format($row->diskon, 0, ',', '.');
                    }
                })
                ->addColumn('deskripsi', function ($row) {
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        // Format as a list with each item prefixed by a dash
                        $obatList = array_map(function ($item) {
                            return "- " . $item;
                        }, $row->racikan_obat_list);

                        // Join with <br> for a line break between each item
                        return implode("<br>", $obatList);
                    } else if ($row->billable_type == 'App\Models\ERM\PaketTindakan') {
                        // For PaketTindakan, show a list of contained tindakan
                        $tindakanList = $row->billable->tindakan()->pluck('nama')->toArray();

                        if (empty($tindakanList)) {
                            return '-';
                        }

                        // Format tindakan list with dash prefixes and line breaks
                        $formattedList = array_map(function ($item) {
                            return "- " . $item;
                        }, $tindakanList);

                        return implode("<br>", $formattedList);
                    } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                        $deskripsi = [];
                        if ($row->billable->aturan_pakai) {
                            $deskripsi[] = "Aturan: " . $row->billable->aturan_pakai;
                        }
                        if ($row->billable->keterangan) {
                            $deskripsi[] = $row->billable->keterangan;
                        }
                        return !empty($deskripsi) ? implode(", ", $deskripsi) : '-';
                    }
                    return '-';
                })
                ->rawColumns(['aksi', 'deskripsi'])
                ->make(true);
        }

        $visitation = Visitation::with('pasien')->findOrFail($visitation_id);
        return view('finance.billing.create', compact('visitation'));
    }

    // Add a new method to handle batch updates
    public function saveBilling(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'edited_items' => 'required|array',
            'deleted_items' => 'required|array',
        ]);

        // Process edited items
        foreach ($request->edited_items as $item) {
            if (isset($item['id'])) {
                $billing = Billing::findOrFail($item['id']);

                // Calculate final amount
                $jumlah = $item['jumlah_raw'];
                if (!empty($item['diskon_raw']) && !empty($item['diskon_type'])) {
                    if ($item['diskon_type'] === '%') {
                        $jumlah -= ($item['diskon_raw'] / 100) * $jumlah;
                    } else {
                        $jumlah -= $item['diskon_raw'];
                    }
                }

                $billing->update([
                    'jumlah' => $jumlah,
                    'diskon' => $item['diskon_raw'],
                    'diskon_type' => $item['diskon_type'],
                ]);
            }
        }

        // Process deleted items
        if (!empty($request->deleted_items)) {
            Billing::whereIn('id', $request->deleted_items)->delete();
        }

        return response()->json(['message' => 'Billing updated successfully']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'billable_id' => 'required',
            'billable_type' => 'required|string',
            'jumlah' => 'required|numeric',
            'diskon' => 'nullable',
            'diskon_type' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $jumlah = $request->jumlah;

        if ($request->diskon_type === '%') {
            $jumlah -= ($request->diskon / 100) * $jumlah;
        } elseif ($request->diskon_type === 'nominal') {
            $jumlah -= $request->diskon;
        }

        Billing::create([
            'visitation_id' => $request->visitation_id,
            'billable_id' => $request->billable_id,
            'billable_type' => $request->billable_type,
            'jumlah' => $jumlah,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->back()->with('success', 'Item billing berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $item = Billing::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item billing dihapus']);
    }
}
