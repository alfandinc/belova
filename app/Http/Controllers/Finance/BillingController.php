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
                $qty = $row->billable_type == 'App\Models\ERM\ResepFarmasi'
                    ? ($row->billable->jumlah ?? 1)
                    : ($row->billable->qty ?? 1);

                // Final calculation: unit_price_after_discount * qty
                $finalPrice = $unitPrice * $qty;

                return 'Rp ' . number_format($finalPrice, 0, ',', '.');
            })
            ->addColumn('qty', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_bungkus;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 1; // Pharmacy fees are counted as single group
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

            // First, save any new items to the billing table
            $newItems = collect($request->items)->filter(function($item) {
                return isset($item['id']) && (
                    str_starts_with($item['id'], 'tindakan-') ||
                    str_starts_with($item['id'], 'lab-') ||
                    str_starts_with($item['id'], 'konsultasi-') ||
                    str_starts_with($item['id'], 'racikan-')
                );
            });

            foreach ($newItems as $item) {
                if (isset($item['deleted']) && $item['deleted']) {
                    continue;
                }

                Billing::create([
                    'visitation_id' => $request->visitation_id,
                    'billable_type' => $item['billable_type'],
                    'billable_id' => $item['billable_id'],
                    'nama_item' => $item['nama_item'],
                    'jumlah' => $item['jumlah_raw'] ?? $item['harga_akhir_raw'],
                    'qty' => $item['qty'] ?? 1,
                    'diskon' => $item['diskon_raw'] ?? 0,
                    'diskon_type' => $item['diskon_type'] ?? 'nominal',
                    'keterangan' => $item['deskripsi'] ?? null,
                ]);
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
                'user_id' => Auth::id(), // Add the current authenticated user ID
                'notes' => $request->notes ?? null, // Add notes if available
            ]);

            // Create invoice items
            foreach ($request->items as $item) {
                if (isset($item['deleted']) && $item['deleted']) {
                    continue; // Skip deleted items
                }
                // Skip Jasa Farmasi from invoice/nota
                if (
                    (isset($item['nama_item']) && strtolower($item['nama_item']) === 'jasa farmasi') ||
                    (isset($item['is_pharmacy_fee']) && $item['is_pharmacy_fee'])
                ) {
                    continue;
                }

                // Handle billable_id - only use if it's numeric, otherwise set to null
                $billableId = null;
                if (isset($item['billable_id']) && is_numeric($item['billable_id'])) {
                    $billableId = intval($item['billable_id']);
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $item['nama_item'] ?? 'Unknown Item',
                    'description' => $item['deskripsi'] ?? '',
                    'quantity' => intval($item['qty'] ?? 1),
                    'unit_price' => floatval($item['jumlah_raw'] ?? $item['harga_akhir_raw'] ?? 0),
                    'discount' => floatval($item['diskon_raw'] ?? 0),
                    'discount_type' => $item['diskon_type'] ?? null,
                    'final_amount' => floatval($item['harga_akhir_raw'] ?? 0) * intval($item['qty'] ?? 1),
                    'billable_type' => $item['billable_type'] ?? null,
                    'billable_id' => $billableId, // This will be null for string IDs
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
        Log::info('=== SAVE BILLING REQUEST START ===');
        Log::info('Request data: ' . json_encode($request->all()));
        Log::info('New items: ' . json_encode($request->input('new_items', [])));
        Log::info('=== SAVE BILLING REQUEST END ===');

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
                Log::info('Processing deleted items: ' . json_encode($request->deleted_items));
                Billing::whereIn('id', $request->deleted_items)->delete();
            }

            // Process edited items
            if (!empty($request->edited_items)) {
                Log::info('Processing edited items: ' . json_encode($request->edited_items));
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

            // Process new items (added through dropdowns)
            if (!empty($request->new_items)) {
                Log::info('Processing new items: ' . json_encode($request->new_items));
                foreach ($request->new_items as $item) {
                    Log::info('Processing new item: ' . json_encode($item));
                    
                    // Skip if this item was marked as deleted (check for both boolean and string)
                    if ((isset($item['deleted']) && ($item['deleted'] === true || $item['deleted'] === 'true'))) {
                        Log::info('Skipping deleted new item: ' . $item['id']);
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
                    
                    Log::info('Created new billing: ' . json_encode($newBilling->toArray()));
                }
            } else {
                Log::info('No new items to process');
            }

            DB::commit();
            Log::info('Save billing completed successfully');
            return response()->json(['success' => true, 'message' => 'Data billing berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Save billing failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
            ->where('status_kunjungan', 2); // Only show completed visits
        
        if ($dokterId) {
            $visitations->where('dokter_id', $dokterId);
        }
        if ($klinikId) {
            $visitations->where('klinik_id', $klinikId);
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
            ->addColumn('tanggal_visit', function ($visitation) {
                return \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->format('j F Y');
            })
            ->addColumn('nama_klinik', function ($visitation) {
                return $visitation->klinik ? $visitation->klinik->nama : 'No Clinic';
            })
            ->addColumn('action', function ($visitation) {
                $action = '<a href="'.route('finance.billing.create', $visitation->id).'" class="btn btn-sm btn-primary">Lihat Billing</a>';
                
                // Add "Cetak Nota" button if invoice exists
                if ($visitation->invoice) {
                    $action .= ' <a href="'.route('finance.invoice.print-nota', $visitation->invoice->id).'" class="btn btn-sm btn-success ml-1" target="_blank">Cetak Nota</a>';
                }
                
                return $action;
            })
            ->rawColumns(['action'])
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
}
