<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use Illuminate\Support\Facades\DB;

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
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        // For racikan, multiply by racikan_bungkus
                        return 'Rp ' . number_format($row->racikan_total_price * $row->racikan_bungkus, 0, ',', '.');
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

                    // Multiply by quantity
                    $qty = $row->billable_type == 'App\Models\ERM\ResepFarmasi'
                        ? ($row->billable->jumlah ?? 1)
                        : ($row->billable->qty ?? 1);

                    return 'Rp ' . number_format($finalPrice * $qty, 0, ',', '.');
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
                        // if ($row->billable->aturan_pakai) {
                        //     $deskripsi[] = "Aturan: " . $row->billable->aturan_pakai;
                        // }
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

    public function createInvoice(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'items' => 'required|array',
            'totals' => 'required|array',
        ]);

        // Start a transaction
        DB::beginTransaction();

        try {
            // Double-check the visitation exists
            $visitation = Visitation::find($request->visitation_id);
            if (!$visitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visitation not found with ID: ' . $request->visitation_id
                ], 404);
            }

            // Get totals from request
            $totals = $request->totals;
            $subtotal = $totals['subtotal'] ?? 0;
            $discountAmount = $totals['discountAmount'] ?? 0;
            $taxAmount = $totals['taxAmount'] ?? 0;
            $grandTotal = $totals['grandTotal'] ?? $subtotal;

            // Create the invoice
            $invoice = Invoice::create([
                'visitation_id' => $request->visitation_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'tax' => $taxAmount, // Changed from tax_amount to tax to match migration
                'discount_type' => $totals['discountType'] ?? null,
                'discount_value' => $totals['discountValue'] ?? 0,
                'tax_percentage' => $totals['taxPercentage'] ?? 0,
                'total_amount' => $grandTotal,
                'status' => 'issued',
                'user_id' => auth()->id(), // Add the current authenticated user ID
                'notes' => $request->notes ?? null, // Add notes if available
            ]);

            // Create invoice items
            foreach ($request->items as $item) {
                if (isset($item['deleted']) && $item['deleted']) {
                    continue; // Skip deleted items
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $item['nama_item'] ?? 'Unknown Item',
                    'description' => $item['deskripsi'] ?? '',
                    'quantity' => intval($item['qty'] ?? 1),
                    'unit_price' => floatval($item['jumlah_raw'] ?? 0),
                    'discount' => floatval($item['diskon_raw'] ?? 0),
                    'discount_type' => $item['diskon_type'] ?? null,
                    'final_amount' => floatval($item['harga_akhir_raw'] ?? 0),
                    'billable_type' => $item['billable_type'] ?? null,
                    'billable_id' => $item['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveBilling(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'edited_items' => 'nullable|array',
            'deleted_items' => 'nullable|array',
            'totals' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Process deleted items
            if (!empty($request->deleted_items)) {
                Billing::whereIn('id', $request->deleted_items)->delete();
            }

            // Process edited items
            if (!empty($request->edited_items)) {
                foreach ($request->edited_items as $item) {
                    // Skip deleted items that may have been edited before deletion
                    if (in_array($item['id'], $request->deleted_items ?? [])) {
                        continue;
                    }

                    $billing = Billing::find($item['id']);
                    if ($billing) {
                        // Update only specific fields that can be edited
                        $billing->jumlah = $item['jumlah_raw'] ?? $billing->jumlah;
                        $billing->diskon = $item['diskon_raw'] ?? null;
                        $billing->diskon_type = $item['diskon_type'] ?? null;
                        $billing->save();
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data billing berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $item = Billing::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item billing dihapus']);
    }
}
