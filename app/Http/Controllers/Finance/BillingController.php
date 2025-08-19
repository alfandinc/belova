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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class BillingController extends Controller
{
    /**
     * Endpoint statistik harian untuk AJAX
     */
    public function statistikPendapatanAjax(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $klinikId = $request->input('klinik_id');

        // Prepare array of dates in range
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $dailyPendapatan = [];
        foreach ($dates as $date) {
            $query = Invoice::whereHas('visitation', function($q) use ($date, $klinikId) {
                $q->whereDate('tanggal_visitation', $date);
                if ($klinikId) $q->where('klinik_id', $klinikId);
            });
            $dailyPendapatan[] = [
                'date' => $date,
                'pendapatan' => $query->sum('amount_paid'),
            ];
        }

        // Total pendapatan, nota, kunjungan
        $invoiceQuery = Invoice::whereHas('visitation', function($q) use ($startDate, $endDate, $klinikId) {
            $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
        $pendapatan = $invoiceQuery->sum('amount_paid');
        $jumlahNota = $invoiceQuery->count();

        $kunjunganQuery = \App\Models\ERM\Visitation::whereBetween('tanggal_visitation', [$startDate, $endDate]);
        if ($klinikId) $kunjunganQuery->where('klinik_id', $klinikId);
        $jumlahKunjungan = $kunjunganQuery->count();

        // Perubahan pendapatan dibandingkan periode sebelumnya (periode sama sebelum startDate)
        $prevStart = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate) + 86400) . ' seconds'));
        $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $invoicePrevQuery = Invoice::whereHas('visitation', function($q) use ($prevStart, $prevEnd, $klinikId) {
            $q->whereBetween('tanggal_visitation', [$prevStart, $prevEnd]);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
        $pendapatanPrev = $invoicePrevQuery->sum('amount_paid');
        $persen = $pendapatanPrev > 0 ? (($pendapatan - $pendapatanPrev) / $pendapatanPrev) * 100 : null;

        return response()->json([
            'pendapatan' => $pendapatan,
            'jumlahNota' => $jumlahNota,
            'jumlahKunjungan' => $jumlahKunjungan,
            'persen' => $persen,
            'dailyPendapatan' => $dailyPendapatan,
        ]);
    }
    /**
     * Menampilkan form rekap penjualan dan tombol download
     */
    public function rekapPenjualanForm(Request $request)
    {
        // Ambil filter
        $date = $request->input('date') ?? date('Y-m-d');
        $klinikId = $request->input('klinik_id');

        // Query invoice hari ini
        $invoiceQuery = Invoice::whereHas('visitation', function($q) use ($date, $klinikId) {
            $q->whereDate('tanggal_visitation', $date);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
        $pendapatan = $invoiceQuery->sum('amount_paid');
        $jumlahNota = $invoiceQuery->count();

        // Query kunjungan hari ini
        $kunjunganQuery = \App\Models\ERM\Visitation::whereDate('tanggal_visitation', $date);
        if ($klinikId) $kunjunganQuery->where('klinik_id', $klinikId);
        $jumlahKunjungan = $kunjunganQuery->count();

        // Query invoice kemarin
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
        $invoiceYesterdayQuery = Invoice::whereHas('visitation', function($q) use ($yesterday, $klinikId) {
            $q->whereDate('tanggal_visitation', $yesterday);
            if ($klinikId) $q->where('klinik_id', $klinikId);
        });
        $pendapatanKemarin = $invoiceYesterdayQuery->sum('amount_paid');

        // Hitung persentase perubahan
        $persen = $pendapatanKemarin > 0 ? (($pendapatan - $pendapatanKemarin) / $pendapatanKemarin) * 100 : null;

        // Ambil daftar klinik
        $kliniks = \App\Models\ERM\Klinik::select('id', 'nama')->orderBy('nama')->get();

        return view('finance.billing.rekap_penjualan_form', compact(
            'pendapatan', 'jumlahNota', 'jumlahKunjungan', 'persen', 'date', 'klinikId', 'kliniks'
        ));
    }

    /**
     * Mendownload file Excel rekap penjualan
     */
    public function downloadRekapPenjualanExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        return (new \App\Exports\Finance\RekapPenjualanExport($startDate, $endDate))->download('rekap-penjualan.xlsx');
    }
    public function index()
    {
        $visitations = Visitation::with(['pasien','klinik'])->get();

        // dd($visitations);
        return view('finance.billing.index', compact('visitations'));
    }

    public function create(Request $request, $visitation_id)
{
    if ($request->ajax()) {
        $billings = Billing::where('visitation_id', $visitation_id)->get();

        // Extract racikan items, pharmacy fees, and regular items
        $racikanGroups = [];
        $pharmacyFeeItems = []; 
        $regularBillings = [];

        foreach ($billings as $billing) {
            // Case 1: Pharmacy fee items (tuslah & embalase)
            if (
    $billing->billable_type == 'App\Models\ERM\JasaFarmasi' || 
    (isset($billing->keterangan) && preg_match('/(tuslah|embalase)/i', $billing->keterangan)) ||
    (isset($billing->nama_item) && preg_match('/(tuslah|embalase)/i', $billing->nama_item))
) {
    $pharmacyFeeItems[] = $billing;
}
            // Case 2: Racikan medication items
            else if (
                $billing->billable_type == 'App\Models\ERM\ResepFarmasi' &&
                $billing->billable->racikan_ke != null &&
                $billing->billable->racikan_ke > 0
            ) {
                $racikanKey = $billing->billable->racikan_ke;
                if (!isset($racikanGroups[$racikanKey])) {
                    $racikanGroups[$racikanKey] = [];
                }
                $racikanGroups[$racikanKey][] = $billing;
            }
            // Case 3: Regular billing items
            else {
                $regularBillings[] = $billing;
            }
        }

        // Create processed billing items (start with regular items)
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

        // Process pharmacy fee items if any
        if (!empty($pharmacyFeeItems)) {
            // Use the first item as base
            $firstPharmacyFee = $pharmacyFeeItems[0];
            
            // Calculate total pharmacy fees
            $totalFees = 0;
            $feeDescriptions = [];
            
            foreach ($pharmacyFeeItems as $item) {
                $totalFees += $item->jumlah;
                
                // Extract description based on available data
                $desc = '';
if (isset($item->keterangan)) {
    // Simply use the keterangan field directly now that it's simplified
    $desc = $item->keterangan;
} else if (isset($item->nama_item)) {
    $desc = $item->nama_item;
}

if (!empty($desc) && !in_array($desc, $feeDescriptions)) {
    $feeDescriptions[] = $desc;
}
            }
            
            // Create a grouped pharmacy service item
            $pharmacyServiceItem = clone $firstPharmacyFee;
            $pharmacyServiceItem->is_pharmacy_fee = true;
            $pharmacyServiceItem->fee_descriptions = $feeDescriptions;
            $pharmacyServiceItem->fee_total_price = $totalFees;
            $pharmacyServiceItem->fee_items_count = count($pharmacyFeeItems);
            
            $processedBillings[] = $pharmacyServiceItem;
        }

        return DataTables::of($processedBillings)
            ->addIndexColumn()
            ->addColumn('nama_item', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return 'Obat Racikan';
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 'Jasa Farmasi';
                } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                    return $row->billable->obat->nama ?? 'N/A';
                } else if ($row->billable_type == 'App\Models\ERM\LabPermintaan') {
                    return 'Lab: ' . ($row->billable->labTest->nama ?? preg_replace('/^Lab: /', '', $row->keterangan ?? 'Test'));
                } else if ($row->billable_type == 'App\Models\ERM\RadiologiPermintaan') {
                    return 'Radiologi: ' . ($row->billable->radiologiTest->nama ?? preg_replace('/^Radiologi: /', '', $row->keterangan ?? 'Test'));
                } else {
                    return $row->nama_item ?? $row->billable->nama ?? $row->keterangan ?? '-';
                }
            })
            ->addColumn('jumlah_raw', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_total_price;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return $row->fee_total_price;
                }
                return $row->jumlah;
            })
            ->addColumn('diskon_raw', function ($row) {
                return $row->diskon ?? '';
            })
            ->addColumn('jumlah', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return 'Rp ' . number_format($row->racikan_total_price, 0, ',', '.');
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 'Rp ' . number_format($row->fee_total_price, 0, ',', '.');
                }
                return 'Rp ' . number_format($row->jumlah, 0, ',', '.');
            })
            ->addColumn('harga_akhir_raw', function ($row) {
                // For pharmacy fees
                if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return $row->fee_total_price;
                }
                
                // For racikan items
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_total_price; // Don't multiply by quantity here, frontend will handle it
                }

                // Get the unit price (harga)  
                $unitPrice = $row->jumlah;
                
                // Apply discount to the unit price
                if ($row->diskon && $row->diskon > 0) {
                    if ($row->diskon_type == '%') {
                        $unitPrice = $unitPrice - ($unitPrice * ($row->diskon / 100));
                    } else {
                        $unitPrice = $unitPrice - $row->diskon;
                    }
                }

                // Return the unit price after discount - frontend will multiply by qty
                return $unitPrice;
            })
            ->addColumn('harga_akhir', function ($row) {
                // For pharmacy fees
                if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 'Rp ' . number_format($row->fee_total_price, 0, ',', '.');
                }
                
                // For racikan items
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return 'Rp ' . number_format($row->racikan_total_price * $row->racikan_bungkus, 0, ',', '.');
                }

                // Get the unit price (harga)
                $unitPrice = $row->jumlah;
                
                // Apply discount to the unit price
                if ($row->diskon && $row->diskon > 0) {
                    if ($row->diskon_type == '%') {
                        $unitPrice = $unitPrice - ($unitPrice * ($row->diskon / 100));
                    } else {
                        $unitPrice = $unitPrice - $row->diskon;
                    }
                }

                // Get quantity
                $qty = isset($row->qty) ? $row->qty : (
                    $row->billable_type == 'App\Models\ERM\ResepFarmasi'
                        ? ($row->billable->jumlah ?? 1)
                        : ($row->billable->qty ?? 1)
                );

                // Final calculation: unit_price_after_discount * qty
                $finalPrice = $unitPrice * $qty;

                return 'Rp ' . number_format($finalPrice, 0, ',', '.');
            })
            ->addColumn('qty', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_bungkus;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 1; // Pharmacy fees are counted as single group
                } else if (isset($row->qty)) {
                    return $row->qty;
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
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    // Display the list of fee items
                    if (empty($row->fee_descriptions)) {
                        return 'Biaya jasa farmasi (' . $row->fee_items_count . ' item)';
                    }
                    
                    // Format fee descriptions with dash prefixes and line breaks
                    $formattedFees = array_map(function ($item) {
                        return "- " . $item;
                    }, $row->fee_descriptions);
                    
                    return implode("<br>", $formattedFees);
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
    // Fetch latest invoice for this visitation (if exists)
    $invoice = \App\Models\Finance\Invoice::where('visitation_id', $visitation_id)->latest()->first();
    return view('finance.billing.create', compact('visitation', 'invoice'));
}

    public function createInvoice(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
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

            // Fetch all billing items for this visitation
            $billingItems = Billing::where('visitation_id', $request->visitation_id)->get();

            // Check stock availability for medication items BEFORE creating invoice
            $stockErrors = [];
            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                    if ($resep && $resep->obat) {
                        $qty = intval($item->qty ?? 1);
                        $currentStock = $resep->obat->stok ?? 0;
                        if ($qty > $currentStock) {
                            $stockErrors[] = "Stok {$resep->obat->nama} tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                        }
                    }
                }
            }
            if (!empty($stockErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi untuk beberapa obat:\n' . implode('\n', $stockErrors)
                ], 400);
            }

            // Get totals from request
            $totals = $request->totals;
            $subtotal = $totals['subtotal'] ?? 0;
            $discountAmount = $totals['discountAmount'] ?? 0;
            $taxAmount = $totals['taxAmount'] ?? 0;
            $grandTotal = $totals['grandTotal'] ?? $subtotal;
            $amountPaid = $totals['amountPaid'] ?? 0;
            $changeAmount = $totals['changeAmount'] ?? 0;
            $paymentMethod = $totals['paymentMethod'] ?? 'cash';

            // Check if invoice exists for this visitation
            $invoice = Invoice::where('visitation_id', $request->visitation_id)->first();
            if ($invoice) {
                // Update existing invoice
                $invoice->update([
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'tax' => $taxAmount,
                    'discount_type' => $totals['discountType'] ?? null,
                    'discount_value' => $totals['discountValue'] ?? 0,
                    'tax_percentage' => $totals['taxPercentage'] ?? 0,
                    'total_amount' => $grandTotal,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changeAmount,
                    'payment_method' => $paymentMethod,
                    'status' => 'issued',
                    'user_id' => Auth::id(),
                    'notes' => $request->notes ?? null,
                ]);
                // Remove old invoice items
                $invoice->items()->delete();
            } else {
                // Create new invoice
                $invoice = Invoice::create([
                    'visitation_id' => $request->visitation_id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'tax' => $taxAmount,
                    'discount_type' => $totals['discountType'] ?? null,
                    'discount_value' => $totals['discountValue'] ?? 0,
                    'tax_percentage' => $totals['taxPercentage'] ?? 0,
                    'total_amount' => $grandTotal,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changeAmount,
                    'payment_method' => $paymentMethod,
                    'status' => 'issued',
                    'user_id' => Auth::id(),
                    'notes' => $request->notes ?? null,
                ]);
            }

            // NOW REDUCE STOCK for medication items
            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                    if ($resep && $resep->obat) {
                        $qty = intval($item->qty ?? 1);
                        $obat = $resep->obat;
                        $obat->stok = max(0, ($obat->stok ?? 0) - $qty);
                        $obat->save();
                        $this->reduceFakturStock($obat->id, $qty);
                        Log::info('Stock reduced via invoice', [
                            'obat_id' => $obat->id,
                            'obat_nama' => $obat->nama,
                            'qty_reduced' => $qty,
                            'remaining_stock' => $obat->stok,
                            'invoice_id' => $invoice->id,
                            'visitation_id' => $request->visitation_id,
                            'user_id' => Auth::id()
                        ]);
                    }
                }
            }

            // Create invoice items from billing items
            foreach ($billingItems as $item) {
                // Skip Jasa Farmasi from invoice/nota
                if (
                    (isset($item->nama_item) && strtolower($item->nama_item) === 'jasa farmasi') ||
                    (isset($item->is_pharmacy_fee) && $item->is_pharmacy_fee)
                ) {
                    continue;
                }
                // Try to get name from related billable model
                $name = $item->nama_item;
                if (empty($name) || $name === 'Unknown Item') {
                    $billableName = null;
                    if (!empty($item->billable_type) && !empty($item->billable_id)) {
                        try {
                            $billableModel = app($item->billable_type)::find($item->billable_id);
                            if ($billableModel) {
                                // Try common name fields
                                if (isset($billableModel->nama)) {
                                    $billableName = $billableModel->nama;
                                } elseif (isset($billableModel->name)) {
                                    $billableName = $billableModel->name;
                                } elseif (isset($billableModel->deskripsi)) {
                                    $billableName = $billableModel->deskripsi;
                                }
                            }
                        } catch (\Exception $e) {}
                    }
                    if (!empty($billableName)) {
                        $name = $billableName;
                    } elseif (!empty($item->deskripsi)) {
                        $name = $item->deskripsi;
                    } elseif (!empty($item->billable_type)) {
                        $name = class_basename($item->billable_type);
                    } else {
                        $name = 'Item';
                    }
                }
                // Fallback for description
                $description = $item->keterangan;
                if (empty($description)) {
                    if (!empty($item->deskripsi)) {
                        $description = $item->deskripsi;
                    } elseif (!empty($item->nama_item)) {
                        $description = $item->nama_item;
                    } else {
                        $description = '';
                    }
                }
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $name,
                    'description' => $description,
                    'quantity' => intval($item->qty ?? 1),
                    'unit_price' => floatval($item->jumlah ?? 0),
                    'discount' => floatval($item->diskon ?? 0),
                    'discount_type' => $item->diskon_type ?? null,
                    'final_amount' => floatval($item->jumlah ?? 0) * intval($item->qty ?? 1),
                    'billable_type' => $item->billable_type ?? null,
                    'billable_id' => $item->billable_id ?? null,
                ]);
            }

            // Add biaya administrasi and biaya ongkir as invoice items if present
            if (!empty($totals['adminFee']) && $totals['adminFee'] > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Biaya Administrasi',
                    'description' => 'Biaya administrasi layanan',
                    'quantity' => 1,
                    'unit_price' => floatval($totals['adminFee']),
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => floatval($totals['adminFee']),
                    'billable_type' => null,
                    'billable_id' => null,
                ]);
            }
            if (!empty($totals['shippingFee']) && $totals['shippingFee'] > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Biaya Ongkir',
                    'description' => 'Biaya pengiriman',
                    'quantity' => 1,
                    'unit_price' => floatval($totals['shippingFee']),
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => floatval($totals['shippingFee']),
                    'billable_type' => null,
                    'billable_id' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil dibuat dan stok telah diupdate',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice', [
                'error' => $e->getMessage(),
                'visitation_id' => $request->visitation_id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveBilling(Request $request)
    {
        // Log::info('=== SAVE BILLING REQUEST START ===');
        // Log::info('Request data: ' . json_encode($request->all()));
        // Log::info('New items: ' . json_encode($request->input('new_items', [])));
        // Log::info('=== SAVE BILLING REQUEST END ===');

        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'edited_items' => 'nullable|array',
            'deleted_items' => 'nullable|array',
            'new_items' => 'nullable|array',
            'totals' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Process deleted items
            if (!empty($request->deleted_items)) {
                // Log::info('Processing deleted items: ' . json_encode($request->deleted_items));
                Billing::whereIn('id', $request->deleted_items)->delete();
            }

            // Process edited items
            if (!empty($request->edited_items)) {
                // Log::info('Processing edited items: ' . json_encode($request->edited_items));
                foreach ($request->edited_items as $item) {
                    // Skip deleted items that may have been edited before deletion
                    if (in_array($item['id'], $request->deleted_items ?? [])) {
                        continue;
                    }

                    // Racikan group edit: update all racikan items proportionally
                    if (isset($item['is_racikan']) && $item['is_racikan'] && isset($item['racikan_total_price'])) {
                        $visitationId = $request->visitation_id;
                        // Use racikan_ke from the edited item (frontend should send this)
                        $racikanKe = $item['racikan_ke'] ?? null;
                        if ($racikanKe !== null) {
                            // Get all resep IDs for this racikan_ke
                            $resepfarmasiIds = DB::table('erm_resepfarmasi')
                                ->where('visitation_id', $visitationId)
                                ->where('racikan_ke', $racikanKe)
                                ->pluck('id')->toArray();
                            // Log::info('Racikan update: racikan_ke='.$racikanKe.' visitation_id='.$visitationId.' resepfarmasiIds='.json_encode($resepfarmasiIds));
                            $racikanBillings = Billing::where('visitation_id', $visitationId)
                                ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                                ->whereIn('billable_id', $resepfarmasiIds)
                                ->get();
                            // Log::info('Racikan update: Billing IDs='.json_encode($racikanBillings->pluck('id')));
                        } else {
                            // fallback: get all racikan for visitation
                            $racikanBillings = Billing::where('visitation_id', $visitationId)
                                ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                                ->get();
                            // Log::info('Racikan update: fallback Billing IDs='.json_encode($racikanBillings->pluck('id')));
                        }
                        $originalTotal = $racikanBillings->sum(function($b){ return (float)$b->jumlah; });
                        $newTotal = (float)$item['racikan_total_price'];
                        $count = $racikanBillings->count();
                        if ($originalTotal > 0 && $count > 0) {
                            $ratio = $newTotal / $originalTotal;
                            $sumSoFar = 0;
                            foreach ($racikanBillings as $i => $racikanBilling) {
                                $oldHarga = (float)$racikanBilling->jumlah;
                                if ($i < $count - 1) {
                                    $newHarga = round($oldHarga * $ratio, 2);
                                    $racikanBilling->jumlah = $newHarga > 0 ? $newHarga : 0;
                                    $racikanBilling->save();
                                    $sumSoFar += $racikanBilling->jumlah;
                                    // Log::info('Racikan update: Billing ID='.$racikanBilling->id.' newHarga='.$racikanBilling->jumlah);
                                } else {
                                    $lastHarga = round($newTotal - $sumSoFar, 2);
                                    $racikanBilling->jumlah = $lastHarga > 0 ? $lastHarga : 0;
                                    $racikanBilling->save();
                                    // Log::info('Racikan update: Billing ID='.$racikanBilling->id.' lastHarga='.$racikanBilling->jumlah);
                                }
                            }
                        } else if ($count > 0) {
                            // If original total is zero, set all to zero except last gets total
                            foreach ($racikanBillings as $i => $racikanBilling) {
                                if ($i < $count - 1) {
                                    $racikanBilling->jumlah = 0;
                                    $racikanBilling->save();
                                    // Log::info('Racikan update: Billing ID='.$racikanBilling->id.' set to 0');
                                } else {
                                    $racikanBilling->jumlah = $newTotal;
                                    $racikanBilling->save();
                                    // Log::info('Racikan update: Billing ID='.$racikanBilling->id.' set to newTotal='.$racikanBilling->jumlah);
                                }
                            }
                        }
                        continue;
                    }

                    // Normal edit for non-racikan items
                    $billing = Billing::find($item['id']);
                    if ($billing) {
                        // Update only specific fields that can be edited
                        $billing->jumlah = $item['jumlah_raw'] ?? $billing->jumlah;
                        $billing->diskon = $item['diskon_raw'] ?? null;
                        $billing->diskon_type = $item['diskon_type'] ?? null;
                        if (isset($item['qty'])) {
        $billing->qty = $item['qty'];
    }
    $billing->save();
                    }
                }
            }

            // Process new items (added through dropdowns)
            if (!empty($request->new_items)) {
                // Log::info('Processing new items: ' . json_encode($request->new_items));
                foreach ($request->new_items as $item) {
                    // Log::info('Processing new item: ' . json_encode($item));
                    
                    // Skip if this item was marked as deleted (check for both boolean and string)
                    if ((isset($item['deleted']) && ($item['deleted'] === true || $item['deleted'] === 'true'))) {
                        // Log::info('Skipping deleted new item: ' . $item['id']);
                        continue;
                    }

                    // Create new billing record
                    $newBilling = Billing::create([
                        'visitation_id' => $request->visitation_id,
                        'billable_type' => $item['billable_type'],
                        'billable_id' => $item['billable_id'],
                        'nama_item' => $item['nama_item'],
                        'jumlah' => $item['harga_akhir_raw'] ?? 0,
                        'qty' => $item['qty'] ?? 1,
                        'diskon' => $item['diskon'] ?? 0,
                        'diskon_type' => $item['diskon_type'] ?? 'nominal',
                        'keterangan' => $item['deskripsi'] ?? null,
                    ]);
                    
                    // Log::info('Created new billing: ' . json_encode($newBilling->toArray()));
                }
            } else {
                // Log::info('No new items to process');
            }

            DB::commit();
            // Log::info('Save billing completed successfully');
            return response()->json(['success' => true, 'message' => 'Data billing berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Save billing failed: ' . $e->getMessage());
            // Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $item = Billing::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item billing dihapus']);
    }

    public function getVisitationsData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        
        $visitations = \App\Models\ERM\Visitation::with(['pasien', 'klinik', 'dokter.user', 'dokter.spesialisasi', 'invoice'])
            ->whereBetween('tanggal_visitation', [$startDate, $endDate . ' 23:59:59'])
            ->where('status_kunjungan', 2);

        if ($dokterId) {
            $visitations->where('dokter_id', $dokterId);
        }
        if ($klinikId) {
            $visitations->where('klinik_id', $klinikId);
        }

        // Status filter: 'belum' (default), 'sudah', or '' (all)
        $statusFilter = $request->input('status_filter', 'belum');
        if ($statusFilter === 'belum') {
            $visitations->where(function($query) {
                $query->whereDoesntHave('invoice')
                      ->orWhereHas('invoice', function($q) {
                          $q->where('amount_paid', 0);
                      });
            });
        } elseif ($statusFilter === 'sudah') {
            $visitations->whereHas('invoice', function($q) {
                $q->where('amount_paid', '>', 0);
            });
        }
        
        return DataTables::of($visitations)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value']) {
                    $query->whereHas('pasien', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%")
                          ->orWhere('id', 'like', "%$search%") ;
                    })
                    ->orWhereHas('dokter.user', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%") ;
                    })
                    ->orWhereHas('dokter.spesialisasi', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%") ;
                    })
                    ->orWhereHas('klinik', function($q) use ($search) {
                        $q->where('nama', 'like', "%$search%") ;
                    })
                    ->orWhere('tanggal_visitation', 'like', "%$search%") ;
                }
            })
            ->addColumn('no_rm', function ($visitation) {
                return $visitation->pasien ? $visitation->pasien->id : '-';
            })
            ->addColumn('nama_pasien', function ($visitation) {
                return $visitation->pasien ? $visitation->pasien->nama : 'No Patient';
            })
            ->addColumn('dokter', function ($visitation) {
                // Show dokter name from related user
                if ($visitation->dokter && $visitation->dokter->user) {
                    return $visitation->dokter->user->name;
                }
                return '-';
            })
            ->addColumn('spesialisasi', function ($visitation) {
                return $visitation->dokter && $visitation->dokter->spesialisasi ? $visitation->dokter->spesialisasi->nama : '-';
            })
            ->addColumn('jenis_kunjungan', function ($visitation) {
                // Map numeric values to labels
                if (isset($visitation->jenis_kunjungan)) {
                    switch ($visitation->jenis_kunjungan) {
                        case 1:
                        case '1':
                            return 'Konsultasi Dokter';
                        case 2:
                        case '2':
                            return 'Beli Produk';
                        case 3:
                        case '3':
                            return 'Laboratorium';
                        default:
                            return $visitation->jenis_kunjungan;
                    }
                }
                return '-';
            })
            ->addColumn('tanggal_visit', function ($visitation) {
                return \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->format('j F Y');
            })
            ->addColumn('nama_klinik', function ($visitation) {
                return $visitation->klinik ? $visitation->klinik->nama : 'No Clinic';
            })
                ->addColumn('status', function ($visitation) {
                    if ($visitation->invoice && $visitation->invoice->amount_paid > 0) {
                        return '<span style="color: #fff; background: #28a745; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Sudah Bayar</span>';
                    }
                    return '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Dibayar</span>';
                })
            ->addColumn('action', function ($visitation) {
                $action = '<a href="'.route('finance.billing.create', $visitation->id).'" class="btn btn-sm btn-primary">Lihat Billing</a>';
                
                // Add "Cetak Nota" buttons if invoice exists
                if ($visitation->invoice) {
                    $action .= ' <a href="'.route('finance.invoice.print-nota', $visitation->invoice->id).'" class="btn btn-sm btn-success ml-1" target="_blank">Cetak Nota</a>';
                    $action .= ' <a href="'.route('finance.invoice.print-nota-v2', $visitation->invoice->id).'" class="btn btn-sm btn-warning ml-1" target="_blank">Cetak Nota v2</a>';
                }
                
                return $action;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    /**
     * Return dokter and klinik lists for filter dropdowns
     */
    public function filters() {
        // Get all dokters with their user relation
        $dokters = \App\Models\ERM\Dokter::with('user')->get()->map(function($dokter) {
            return [
                'id' => $dokter->id,
                'name' => $dokter->user ? $dokter->user->name : 'Tanpa Nama',
            ];
        });
        $kliniks = \App\Models\ERM\Klinik::select('id', 'nama')->orderBy('nama')->get();
        return response()->json([
            'dokters' => $dokters,
            'kliniks' => $kliniks,
        ]);
    }

    /**
     * Reduce stock in faktur beli items using FIFO (First Expiry First Out)
     */
    private function reduceFakturStock($obatId, $qty)
    {
        $remaining = $qty;
        $fakturItems = \App\Models\ERM\FakturBeliItem::where('obat_id', $obatId)
            ->where('sisa', '>', 0)
            ->whereNotNull('expiration_date')
            ->orderBy('expiration_date', 'asc')
            ->get();
            
        foreach ($fakturItems as $item) {
            if ($remaining <= 0) break;
            $take = min($item->sisa, $remaining);
            $item->sisa -= $take;
            $item->save();
            $remaining -= $take;
            
            Log::info('Faktur stock reduced', [
                'faktur_item_id' => $item->id,
                'obat_id' => $obatId,
                'qty_reduced' => $take,
                'remaining_sisa' => $item->sisa,
                'remaining_to_reduce' => $remaining
            ]);
        }
        
        if ($remaining > 0) {
            Log::warning('Could not reduce all faktur stock', [
                'obat_id' => $obatId,
                'remaining_unreduced' => $remaining
            ]);
        }
    }
}
