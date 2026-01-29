<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\Finance\Billing;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\Piutang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Services\ERM\StokService;
use App\Models\ERM\PaketRacikan;


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
        $invoiceQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate])
            ->where('finance_invoices.amount_paid', '>', 0);
        if ($klinikId) {
            $invoiceQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        // Debug: Log raw SQL and invoice IDs
        \Illuminate\Support\Facades\Log::info('Finance statistikPendapatanAjax SQL:', ['sql' => $invoiceQuery->toSql(), 'bindings' => $invoiceQuery->getBindings()]);
        $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
        \Illuminate\Support\Facades\Log::info('Finance statistikPendapatanAjax Invoice IDs:', ['ids' => $invoiceIds]);
        $pendapatan = $invoiceQuery->sum('finance_invoices.total_amount');
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
        $invoiceQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
            ->whereDate('erm_visitations.tanggal_visitation', $date)
            ->where('finance_invoices.amount_paid', '>', 0);
        if ($klinikId) {
            $invoiceQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        // Debug: Log raw SQL and invoice IDs
        \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm SQL:', ['sql' => $invoiceQuery->toSql(), 'bindings' => $invoiceQuery->getBindings()]);
        $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
        \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Invoice IDs:', ['ids' => $invoiceIds]);
            $invoiceIds = $invoiceQuery->pluck('finance_invoices.id');
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Invoice IDs:', ['ids' => $invoiceIds]);
            $pendapatan = $invoiceQuery->sum('finance_invoices.total_amount');
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
            $invoiceYesterdayQuery = Invoice::join('erm_visitations', 'finance_invoices.visitation_id', '=', 'erm_visitations.id')
                ->whereDate('erm_visitations.tanggal_visitation', $yesterday)
                ->where('finance_invoices.amount_paid', '>', 0);
            if ($klinikId) {
                $invoiceYesterdayQuery->where('erm_visitations.klinik_id', $klinikId);
            }
            // Debug: Log raw SQL and invoice IDs for yesterday
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Yesterday SQL:', ['sql' => $invoiceYesterdayQuery->toSql(), 'bindings' => $invoiceYesterdayQuery->getBindings()]);
            $invoiceYesterdayIds = $invoiceYesterdayQuery->pluck('finance_invoices.id');
            \Illuminate\Support\Facades\Log::info('Finance rekapPenjualanForm Yesterday Invoice IDs:', ['ids' => $invoiceYesterdayIds]);
            $pendapatanKemarin = $invoiceYesterdayQuery->sum('finance_invoices.total_amount');

        // Hitung persentase perubahan
        $persen = $pendapatanKemarin > 0 ? (($pendapatan - $pendapatanKemarin) / $pendapatanKemarin) * 100 : null;

        // Ambil daftar klinik dan dokter
        $kliniks = \App\Models\ERM\Klinik::select('id', 'nama')->orderBy('nama')->get();
        $dokters = \App\Models\ERM\Dokter::with('user')->orderBy('id')->get();

        return view('finance.billing.rekap_penjualan_form', compact(
            'pendapatan', 'jumlahNota', 'jumlahKunjungan', 'persen', 'date', 'klinikId', 'kliniks', 'dokters'
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
        $klinikId = $request->input('klinik_id');
        $dokterId = $request->input('dokter_id');
    // Ensure RekapPenjualanExport uses total_amount for revenue calculation
    return (new \App\Exports\Finance\RekapPenjualanExport($startDate, $endDate, $klinikId, $dokterId, 'total_amount'))->download('rekap-penjualan.xlsx');
    }
    public function index()
    {
        $visitations = Visitation::with(['pasien','klinik'])->get();

        // dd($visitations);
        return view('finance.billing.index', compact('visitations'));
    }

    /**
     * Send notification from Billing view to all Farmasi users
     */
    public function sendNotifToFarmasi(Request $request)
    {
        // Only allow finance roles (Kasir or Admin) to send from billing view
        $user = Auth::user();
        if (!($user && ($user->hasRole('Kasir') || $user->hasRole('Admin')))) {
            return response()->json(['success' => false], 403);
        }

        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $message = $request->message;
        $farmasis = \App\Models\User::role('Farmasi')->get();

        Log::info('Billing -> sendNotifToFarmasi called by user: ' . ($user->id ?? 'unknown') . ' (' . ($user->name ?? '') . ')', ['farmasi_count' => $farmasis->count()]);

        $sent = 0;
        $failed = [];
        foreach ($farmasis as $farmasi) {
            try {
                // Avoid duplicate unread notifications with same message
                $already = false;
                // track if we applied a promo-derived discount so we can store nominal equivalent
                $appliedPromo = false;
                $promoBase = null;
                $promoPercent = null;

                try {
                    $already = $farmasi->unreadNotifications()->where('data->message', $message)->exists();
                } catch (\Exception $ex) {
                    // Fallback for DB that doesn't support JSON where: do a PHP-level check
                    $already = $farmasi->unreadNotifications->contains(function($n) use ($message) {
                        return isset($n->data['message']) && $n->data['message'] === $message;
                    });
                }

                if (!$already) {
                    $farmasi->notify(new \App\Notifications\BillingToFarmasiNotification($message));
                    $sent++;
                } else {
                    Log::info('Skipping duplicate notification for Farmasi user ID: ' . $farmasi->id);
                }

                // Also set the cache keys used by ERM NotificationController so ERM pages pick it up
                $indexKey = 'farmasi_notification_index_' . $farmasi->id;
                Cache::put($indexKey, [
                    'message' => $message,
                    'type' => 'billing',
                    'timestamp' => time()
                ], 300);

                $createKey = 'farmasi_notification_create_' . $farmasi->id;
                Cache::put($createKey, [
                    'message' => $message,
                    'type' => 'billing_create',
                    'timestamp' => time()
                ], 300);

            } catch (\Exception $e) {
                Log::error('Failed to notify Farmasi user ID: ' . $farmasi->id . ' Error: ' . $e->getMessage());
                $failed[] = $farmasi->id;
            }
        }

        Log::info('Billing -> sendNotifToFarmasi result', ['total' => $farmasis->count(), 'sent' => $sent, 'failed' => $failed]);

        return response()->json(['success' => true, 'total' => $farmasis->count(), 'sent' => $sent, 'failed' => $failed]);
    }

    /**
     * Poll for unread notifications for Farmasi
     */
    public function getNotif(Request $request)
    {
        $user = Auth::user();
        // Allow both Farmasi and Kasir users (and keep behavior for Farmasi)
        if (!$user || (! $user->hasRole('Farmasi') && ! $user->hasRole('Kasir'))) {
            return response()->json(['new' => false]);
        }
        $notif = $user->unreadNotifications()->latest()->first();
        if ($notif) {
            $notif->markAsRead();
            return response()->json(['new' => true, 'message' => $notif->data['message'] ?? '', 'sender' => $notif->data['sender'] ?? '']);
        }
        return response()->json(['new' => false]);
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
            // Case 3: Skip bundled obat items (don't show in billing list)
            else if (
                $billing->billable_type == 'App\Models\ERM\Obat' &&
                isset($billing->keterangan) && 
                str_contains($billing->keterangan, 'Obat Bundled:')
            ) {
                // Skip bundled obat - don't add to any display array
                continue;
            }
            // Case 4: Regular billing items
            else {
                $regularBillings[] = $billing;
            }
        }

        // Create processed billing items (start with regular items)
        $processedBillings = $regularBillings;

        // Preload active PaketRacikans with details for matching
        $activePaketRacikans = PaketRacikan::with(['details' => function($q){ $q->select('id','paket_racikan_id','obat_id','dosis'); }])
            ->where('is_active', true)
            ->get(['id','nama_paket','is_active']);

        // Apply active promos to processed billing items: set diskon (%) when promo matches
        try {
            $today = \Carbon\Carbon::today()->format('Y-m-d');
            foreach ($processedBillings as $pbIndex => $pb) {
                // skip racikan and pharmacy fee rows
                if (isset($pb->is_racikan) || isset($pb->is_pharmacy_fee)) continue;

                // collect candidate IDs to match PromoItem.item_id
                $candidates = [];
                if (isset($pb->billable_id)) $candidates[] = $pb->billable_id;
                if (isset($pb->billable) && isset($pb->billable->obat) && isset($pb->billable->obat->id)) $candidates[] = $pb->billable->obat->id;
                if (isset($pb->billable) && isset($pb->billable->tindakan_id)) $candidates[] = $pb->billable->tindakan_id;
                if (isset($pb->billable) && isset($pb->billable->id)) $candidates[] = $pb->billable->id;
                $candidates = array_values(array_filter(array_unique($candidates)));
                if (empty($candidates)) continue;

                $promoItems = \App\Models\Marketing\PromoItem::whereIn('item_id', $candidates)
                    ->whereIn('item_type', ['tindakan','obat'])
                    ->whereHas('promo', function($q) use ($today){
                        $q->where(function($q2) use ($today){
                            $q2->whereNotNull('start_date')->whereNotNull('end_date')
                                ->where('start_date','<=',$today)
                                ->where('end_date','>=',$today);
                        })->orWhere(function($q2) use ($today){
                            $q2->whereNotNull('start_date')->whereNull('end_date')
                                ->where('start_date','<=',$today);
                        })->orWhere(function($q2) use ($today){
                            $q2->whereNull('start_date')->whereNotNull('end_date')
                                ->where('end_date','>=',$today);
                        });
                    })->get();

                if ($promoItems->isEmpty()) continue;

                // choose highest discount_percent among matching promo items
                $max = $promoItems->max('discount_percent');
                if ($max > 0) {
                    $pb->diskon = $max;
                    $pb->diskon_type = '%';

                    // Choose the winning promo item and pick its discounted price if available
                    $winning = $promoItems->firstWhere('discount_percent', $max);
                    $basePrice = null;

                    if ($winning) {
                        if ($winning->item_type === 'tindakan') {
                            $t = \App\Models\ERM\Tindakan::find($winning->item_id);
                            $basePrice = $t->harga_diskon ?? $t->harga ?? null;
                        } elseif ($winning->item_type === 'obat') {
                            $o = \App\Models\ERM\Obat::withInactive()->find($winning->item_id);
                            $basePrice = $o->harga_diskon ?? $o->harga_net ?? null;
                        }
                    }

                    // Common fallbacks
                    if (!$basePrice && isset($pb->billable)) {
                        $basePrice = $pb->billable->harga_diskon ?? $pb->billable->unit_price ?? null;
                    }
                    if (!$basePrice) {
                        $basePrice = $pb->jumlah ?? 0;
                    }

                    if ($basePrice) {
                        $pb->promo_price_base = $basePrice;
                    }
                }

                // write back
                $processedBillings[$pbIndex] = $pb;
            }
        } catch (\Exception $e) {
            // if promo application fails, continue without promo discounts
            Log::warning('Failed to apply promos to billing rows: '.$e->getMessage());
        }

        // Process each racikan group
        foreach ($racikanGroups as $racikanKey => $racikanItems) {
            // Use the first item as base
            $firstItem = $racikanItems[0];

            // Calculate total price for the racikan
            $totalPrice = 0;
            $obatList = [];
            $obatIds = [];
            $bungkus = 0;

            foreach ($racikanItems as $item) {
                $totalPrice += $item->jumlah;
                $obatList[] = $item->billable->obat->nama ?? 'Obat Tidak Diketahui';
                // collect obat ids for frontend stock checks
                if (isset($item->billable) && isset($item->billable->obat) && isset($item->billable->obat->id)) {
                    $obatIds[] = $item->billable->obat->id;
                }
                // Get bungkus from the first item only (should be same for all items in racikan)
                if ($bungkus == 0) {
                    $bungkus = $item->billable->bungkus ?? 0;
                }
            }

            // Clone the first item and modify its properties for display
            $racikanItem = clone $firstItem;
            $racikanItem->is_racikan = true;
            $racikanItem->racikan_obat_list = $obatList;
            // Expose obat IDs so frontend can fetch stock for each component
            $racikanItem->racikan_obat_ids = $obatIds;
            $racikanItem->racikan_total_price = $totalPrice;
            $racikanItem->racikan_bungkus = $bungkus;
            $racikanItem->nama_item = 'Racikan ' . $racikanKey; // Explicitly set the name with racikan number

            // Build per-component metadata including stored stok_dikurangi (ResepFarmasi.jumlah)
            $components = [];
            foreach ($racikanItems as $it) {
                try {
                    $billable = $it->billable ?? null;
                    $obatModel = $billable && isset($billable->obat) ? $billable->obat : null;
                    $components[] = [
                        'obat_id' => $obatModel ? ($obatModel->id ?? null) : null,
                        'nama' => $obatModel ? ($obatModel->nama ?? '') : ($it->billable_name ?? ''),
                        // stok_dikurangi persisted into ResepFarmasi.jumlah for racikan components (allow decimals)
                        'stok_dikurangi' => $billable ? floatval($billable->jumlah ?? 0) : 0,
                        // include dosis to support PaketRacikan matching (string compare)
                        'dosis' => isset($billable->dosis) ? trim((string)$billable->dosis) : null,
                    ];
                } catch (\Exception $e) {
                    $components[] = ['obat_id' => null, 'nama' => '', 'stok_dikurangi' => 0, 'dosis' => null];
                }
            }
            $racikanItem->racikan_components = $components;

            // Try to match components with an active PaketRacikan (obat_id + dosis match, order-insensitive)
            try {
                // Helper to normalize dosis: extract numeric part if present, else trimmed lower-case string
                $normalizeDose = function($val) {
                    if ($val === null) return '';
                    $s = trim(strtolower((string)$val));
                    // replace commas with dots, remove thousands separators
                    $s = str_replace([','], ['.'], $s);
                    // extract first number (integer/decimal)
                    if (preg_match('/\d+(?:\.\d+)?/', $s, $m)) {
                        return rtrim(rtrim($m[0], '0'), '.') ?: $m[0];
                    }
                    return $s; // fallback: raw string
                };
                // Build a normalized map from components
                $compMap = [];
                foreach ($components as $c) {
                    if (!$c['obat_id']) continue;
                    $key = $c['obat_id'] . '|' . $normalizeDose($c['dosis'] ?? '');
                    $compMap[$key] = true;
                }

                foreach ($activePaketRacikans as $paket) {
                    $details = $paket->details;
                    if (!$details || $details->count() === 0) continue;
                    if ($details->count() !== count($compMap)) continue; // quick size check

                    $allMatch = true;
                    foreach ($details as $d) {
                        $dKey = ($d->obat_id ?? '0') . '|' . $normalizeDose($d->dosis ?? '');
                        if (!isset($compMap[$dKey])) {
                            $allMatch = false;
                            break;
                        }
                    }

                    if ($allMatch) {
                        // Found matching paket
                        $racikanItem->paket_racikan_name = $paket->nama_paket;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // non-fatal: leave paket name unset
                Log::warning('Racikan paket matching failed: ' . $e->getMessage());
            }

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

        try {
            return DataTables::of($processedBillings)
                ->addIndexColumn()
            ->addColumn('obat_id', function ($row) {
                try {
                    // If this billing row is a ResepFarmasi, expose obat id for frontend
                    if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                        $resep = $row->billable;
                        if ($resep && isset($resep->obat)) return $resep->obat->id;
                    }

                    // If billing row itself references an Obat directly
                    if ($row->billable_type == 'App\\Models\\ERM\\Obat') {
                        return $row->billable_id;
                    }
                } catch (\Exception $e) {
                    // swallow and return null
                }
                return null;
            })
            ->addColumn('nama_item', function ($row) {
                    // Use optional() to avoid "property on null" errors when relations are missing
                    if (isset($row->is_racikan) && $row->is_racikan) {
                        // Prefer displaying matched PaketRacikan name if available
                        if (isset($row->paket_racikan_name) && $row->paket_racikan_name) {
                            return $row->paket_racikan_name;
                        }
                        return 'Obat Racikan';
                    } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                        return 'Jasa Farmasi';
                    } else if ($row->billable_type == 'App\Models\ERM\ResepFarmasi') {
                        return optional(optional($row->billable)->obat)->nama ?? 'N/A';
                    } else if ($row->billable_type == 'App\Models\ERM\LabPermintaan') {
                        $labName = optional(optional($row->billable)->labTest)->nama;
                        return 'Lab: ' . ($labName ?? preg_replace('/^Lab: /', '', $row->keterangan ?? 'Test'));
                    } else if ($row->billable_type == 'App\Models\ERM\RadiologiPermintaan') {
                        $radName = optional(optional($row->billable)->radiologiTest)->nama;
                        return 'Radiologi: ' . ($radName ?? preg_replace('/^Radiologi: /', '', $row->keterangan ?? 'Test'));
                    } else {
                        return $row->nama_item ?? optional($row->billable)->nama ?? $row->keterangan ?? '-';
                    }
            })
            ->addColumn('jumlah_raw', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_total_price;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return $row->fee_total_price;
                }
                return $row->jumlah ?? 0;
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
                // If a promo_price_base is provided (promo applies and uses a special base), prefer it for percentage discounts
                $unitPrice = $row->jumlah;
                if (isset($row->promo_price_base) && $row->promo_price_base && $row->diskon_type == '%') {
                    $unitPrice = $row->promo_price_base;
                }

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
                // If a promo_price_base is provided (promo applies and uses a special base), prefer it for percentage discounts
                $unitPrice = $row->jumlah;
                if (isset($row->promo_price_base) && $row->promo_price_base && $row->diskon_type == '%') {
                    $unitPrice = $row->promo_price_base;
                }

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

                // no diagnostic logging in production

                return 'Rp ' . number_format($finalPrice, 0, ',', '.');
            })
            ->addColumn('qty', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    return $row->racikan_bungkus ?? 0;
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    return 1; // Pharmacy fees are counted as single group
                } else if (isset($row->qty)) {
                    return $row->qty;
                } else if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                    return optional($row->billable)->jumlah ?? 1;
                }
                return optional($row->billable)->qty ?? 1;
            })
            ->addColumn('diskon', function ($row) {
                if (!$row->diskon || $row->diskon == 0) {
                    return '-';
                }

                // If percent promo and a promo_price_base exists which is lower than original amount,
                // show the original fixed discount (original - promo_base) plus the percent.
                if ($row->diskon_type == '%') {
                    $percentText = number_format($row->diskon, 2) . '%';

                    // Determine original unit amount where possible
                    $originalAmount = null;
                    if (isset($row->jumlah) && is_numeric($row->jumlah)) {
                        $originalAmount = $row->jumlah;
                    } elseif (isset($row->racikan_total_price)) {
                        $originalAmount = $row->racikan_total_price;
                    } elseif (isset($row->fee_total_price)) {
                        $originalAmount = $row->fee_total_price;
                    }

                    if ($originalAmount && isset($row->promo_price_base) && is_numeric($row->promo_price_base) && $row->promo_price_base < $originalAmount) {
                        $fixedDiscount = $originalAmount - $row->promo_price_base;
                        if ($fixedDiscount > 0) {
                            return 'Rp ' . number_format($fixedDiscount, 0, ',', '.') . ' + ' . $percentText;
                        }
                    }

                    return $percentText;
                } else {
                    return 'Rp ' . number_format($row->diskon, 0, ',', '.');
                }
            })
            ->addColumn('deskripsi', function ($row) {
                if (isset($row->is_racikan) && $row->is_racikan) {
                    // Format as a list with each item prefixed by a dash
                    $obatList = array_map(function ($item) {
                        return "- " . $item;
                    }, $row->racikan_obat_list ?? []);

                    // Join with <br> for a line break between each item
                    return implode("<br>", $obatList);
                } else if (isset($row->is_pharmacy_fee) && $row->is_pharmacy_fee) {
                    // Display the list of fee items
                    if (empty($row->fee_descriptions)) {
                        return 'Biaya jasa farmasi (' . ($row->fee_items_count ?? 0) . ' item)';
                    }
                    
                    // Format fee descriptions with dash prefixes and line breaks
                    $formattedFees = array_map(function ($item) {
                        return "- " . $item;
                    }, $row->fee_descriptions ?? []);
                    
                    return implode("<br>", $formattedFees);
                } else if ($row->billable_type == 'App\\Models\\ERM\\PaketTindakan') {
                    // For PaketTindakan, show a list of contained tindakan
                    $tindakanList = optional($row->billable)->tindakan ? optional($row->billable)->tindakan()->pluck('nama')->toArray() : [];

                    if (empty($tindakanList)) {
                        return '-';
                    }

                    // Format tindakan list with dash prefixes and line breaks
                    $formattedList = array_map(function ($item) {
                        return "- " . $item;
                    }, $tindakanList);

                    return implode("<br>", $formattedList);
                } else if ($row->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                    $deskripsi = [];
                    if (optional($row->billable)->keterangan) {
                        $deskripsi[] = $row->billable->keterangan;
                    }
                    return !empty($deskripsi) ? implode(", ", $deskripsi) : '-';
                }
                return '-';
            })
                ->rawColumns(['aksi', 'deskripsi'])
                ->make(true);
        } catch (\Exception $e) {
            // Log and return JSON error so remote clients (browser) can see the message
            Log::error('Error in BillingController::create AJAX DataTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'visitation_id' => $visitation_id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while generating billing data: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    $visitation = Visitation::with('pasien')->findOrFail($visitation_id);
    // Fetch latest invoice for this visitation (if exists)
    $invoice = \App\Models\Finance\Invoice::where('visitation_id', $visitation_id)->latest()->first();
    
    // Get all available gudangs for dropdown
    $gudangs = Gudang::orderBy('nama')->get();
    
    // Get active gudang mappings for auto-selection
    $gudangMappings = [
        'resep' => GudangMapping::getDefaultGudangId('resep'),
        'tindakan' => GudangMapping::getDefaultGudangId('tindakan'),
        'kode_tindakan' => GudangMapping::getDefaultGudangId('kode_tindakan'),
    ];
    
    return view('finance.billing.create', compact('visitation', 'invoice', 'gudangs', 'gudangMappings'));
}

    public function createInvoice(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'totals' => 'required|array',
            'gudang_selections' => 'nullable|array', // Array of item_key => gudang_id
        ]);

        DB::beginTransaction();

        try {
            // Check if visitation exists
            $visitation = Visitation::find($request->visitation_id);
            if (!$visitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visitation not found with ID: ' . $request->visitation_id
                ], 404);
            }

            // Get existing invoice if it exists
            $existingInvoice = Invoice::where('visitation_id', $request->visitation_id)->first();

            // Fetch all billing items for this visitation
            $billingItems = Billing::where('visitation_id', $request->visitation_id)->get();

            // Quick debug log to help determine why stock reduction may be skipped
            try {
                Log::info('createInvoice debug payload', [
                    'visitation_id' => $request->visitation_id,
                    'existing_invoice_id' => $existingInvoice ? $existingInvoice->id : null,
                    'existing_invoice_amount_paid' => $existingInvoice ? floatval($existingInvoice->amount_paid ?? 0) : null,
                    'billing_item_count' => $billingItems->count(),
                    'totals_present' => $request->has('totals'),
                    'gudang_selections_present' => $request->has('gudang_selections'),
                    'gudang_selections_sample' => is_array($request->input('gudang_selections', [])) ? array_slice($request->input('gudang_selections', []), 0, 5) : $request->input('gudang_selections', []),
                    'totals_payload' => $request->input('totals') ?? null,
                ]);
            } catch (\Exception $e) {
                // do not fail flow if logging fails
                Log::warning('Failed to write createInvoice debug log: ' . $e->getMessage());
            }

            // Check stock availability for medication items BEFORE creating invoice
            // If frontend provided per-item gudang selections, prefer validating against
            // that specific gudang; otherwise fallback to total stock across all gudangs.
            $stockErrors = [];
            $kodeTindakanObats = [];
            $labRequiredObats = [];
            $gudangSelections = $request->input('gudang_selections', []);
            
            foreach ($billingItems as $item) {
                // Check ResepFarmasi items
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                    if ($resep && $resep->obat) {
                        $qty = floatval($item->qty ?? 1);
                        
                        // Skip racikan items for individual stock validation (will be handled in bulk)
                        if ($resep->racikan_ke > 0) {
                            continue;
                        }
                        
                        // If a specific gudang was selected for this billing row, validate against that gudang only.
                        // Frontend sends keys keyed by billing row id (the DataTable row id). As a secondary
                        // option we also support the legacy key format "resep_{obatId}".
                        $billingKey = $item->id;
                        $selectedGudangId = null;
                        if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                            $selectedGudangId = $gudangSelections[$billingKey];
                        } elseif (isset($gudangSelections['resep_' . $resep->obat->id]) && $gudangSelections['resep_' . $resep->obat->id]) {
                            $selectedGudangId = $gudangSelections['resep_' . $resep->obat->id];
                        }

                        if ($selectedGudangId) {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $resep->obat->id)
                                ->where('gudang_id', $selectedGudangId)
                                ->sum('stok');
                        } else {
                            // Fallback to total stock across all gudangs (existing behavior)
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $resep->obat->id)
                                ->sum('stok');
                        }
                        
                        if ($qty > $currentStock) {
                            $stockErrors[] = "Stok {$resep->obat->nama} tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                        }
                    }
                }
                // Check bundled Obat items
                else if (
                    isset($item->billable_type) && 
                    $item->billable_type === 'App\\Models\\ERM\\Obat' &&
                    isset($item->keterangan) && 
                    str_contains($item->keterangan, 'Obat Bundled:')
                ) {
                    $obat = \App\Models\ERM\Obat::find($item->billable_id);
                    if ($obat) {
                        $qty = floatval($item->qty ?? 1);
                        
                        // Respect selected gudang for bundled Obat if provided (key: tindakan_{obatId})
                        $billingKey = $item->id;
                        $selectedGudangId = null;
                        if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                            $selectedGudangId = $gudangSelections[$billingKey];
                        } elseif (isset($gudangSelections['tindakan_' . $obat->id]) && $gudangSelections['tindakan_' . $obat->id]) {
                            $selectedGudangId = $gudangSelections['tindakan_' . $obat->id];
                        }

                        if ($selectedGudangId) {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                ->where('gudang_id', $selectedGudangId)
                                ->sum('stok');
                        } else {
                            $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                ->sum('stok');
                        }
                            
                        if ($qty > $currentStock) {
                            $stockErrors[] = "Stok {$obat->nama} (bundled) tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                        }
                    }
                }
            }
            
            // Check stock for kode tindakan medications
            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                    $riwayatTindakan = \App\Models\ERM\RiwayatTindakan::find($item->billable_id);
                    if ($riwayatTindakan) {
                        // Get medications from kode tindakan for this riwayat tindakan
                        $kodeTindakanMeds = DB::table('erm_riwayat_tindakan_obat')
                            ->where('riwayat_tindakan_id', $riwayatTindakan->id)
                            ->get();
                            
                        Log::info('Processing kode tindakan medications', [
                            'riwayat_tindakan_id' => $riwayatTindakan->id,
                            'medication_count' => $kodeTindakanMeds->count(),
                            'visitation_id' => $request->visitation_id
                        ]);
                            
                        foreach ($kodeTindakanMeds as $kodeTindakanMed) {
                            $obat = \App\Models\ERM\Obat::find($kodeTindakanMed->obat_id);
                                if ($obat) {
                                $qty = floatval($kodeTindakanMed->qty ?? 1);
                                // For kode tindakan medications, prefer selected gudang key: kode_tindakan_{obatId}
                                $billingKey = $item->id;
                                $selectedGudangId = null;
                                if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                                    $selectedGudangId = $gudangSelections[$billingKey];
                                } elseif (isset($gudangSelections['kode_tindakan_' . $obat->id]) && $gudangSelections['kode_tindakan_' . $obat->id]) {
                                    $selectedGudangId = $gudangSelections['kode_tindakan_' . $obat->id];
                                }

                                if ($selectedGudangId) {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->where('gudang_id', $selectedGudangId)
                                        ->sum('stok');
                                } else {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->sum('stok');
                                }
                                    
                                if ($qty > $currentStock) {
                                    $stockErrors[] = "Stok {$obat->nama} (kode tindakan) tidak mencukupi. Dibutuhkan: {$qty}, Tersedia: {$currentStock}";
                                }
                                
                                // Store for later stock reduction
                                $kodeTindakanObats[] = [
                                    'obat_id' => $obat->id,
                                    'qty' => $qty,
                                    'riwayat_tindakan_id' => $riwayatTindakan->id,
                                    'kode_tindakan_id' => $kodeTindakanMed->kode_tindakan_id,
                                    'billing_id' => $item->id // preserve billing id so we can map gudang selection later
                                ];
                            }
                        }
                    }
                }
            }
            // Check stock for Lab Test medications (from LabPermintaan -> LabTest -> obats with dosis)
            foreach ($billingItems as $item) {
                if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\LabPermintaan') {
                    $labPermintaan = \App\Models\ERM\LabPermintaan::with('labTest.obats')->find($item->billable_id);
                    if ($labPermintaan && $labPermintaan->labTest) {
                        // Normalize qty for lab test: guard against values like "1,00" being saved as 100
                        $qtyTestRaw = $item->qty ?? 1;
                        if (is_string($qtyTestRaw)) {
                            // Convert Indonesian/legacy formats: "1,00" -> 1.00, "1.000,25" -> 1000.25
                            $normalized = str_replace('.', '', $qtyTestRaw);
                            $normalized = str_replace(',', '.', $normalized);
                            $qtyTest = floatval($normalized);
                        } else {
                            $qtyTest = floatval($qtyTestRaw);
                        }
                        // If qty looks accidentally scaled by 100 (e.g., 100 from "1,00"), normalize back
                        if ($qtyTest >= 100 && $qtyTest <= 1000) {
                            $qtyTest = $qtyTest / 100.0;
                        }

                        foreach ($labPermintaan->labTest->obats as $obat) {
                            // Robust parse for pivot dosis which may be stored with comma decimals
                            $dosisRaw = $obat->pivot->dosis ?? 0;
                            if (is_string($dosisRaw)) {
                                $dosis = floatval(str_replace(',', '.', $dosisRaw));
                            } else {
                                $dosis = floatval($dosisRaw);
                            }
                            $required = $dosis * $qtyTest;
                            if ($required <= 0) { continue; }

                            // Prefer selected gudang for this billing row or typed key lab_{obatId}
                            $billingKey = $item->id;
                            $selectedGudangId = null;
                            if ($billingKey && isset($gudangSelections[$billingKey]) && $gudangSelections[$billingKey]) {
                                $selectedGudangId = $gudangSelections[$billingKey];
                            } elseif (isset($gudangSelections['lab_' . $obat->id]) && $gudangSelections['lab_' . $obat->id]) {
                                $selectedGudangId = $gudangSelections['lab_' . $obat->id];
                            }

                            if ($selectedGudangId) {
                                $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                    ->where('gudang_id', $selectedGudangId)
                                    ->sum('stok');
                            } else {
                                // Align validation with reduction: resolve gudang via mapping (same as getGudangForItem)
                                $mappedGudangId = $this->getGudangForItem($request, $obat->id, 'lab', $item->id);
                                if ($mappedGudangId) {
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->where('gudang_id', $mappedGudangId)
                                        ->sum('stok');
                                } else {
                                    // Fallback: total across all gudangs if no mapping found
                                    $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)
                                        ->sum('stok');
                                }
                            }

                            if ($required > $currentStock) {
                                $testName = $labPermintaan->labTest->nama ?? 'Lab Test';
                                $stockErrors[] = "Stok {$obat->nama} untuk lab ({$testName}) tidak mencukupi. Dibutuhkan: {$required}, Tersedia: {$currentStock}";
                            }

                            // record for later reduction
                            $labRequiredObats[] = [
                                'billing_id' => $item->id,
                                'obat_id' => $obat->id,
                                'qty' => $required,
                                'lab_test_id' => $labPermintaan->labTest->id,
                            ];
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

            // No invoice-level current_hpp: HPP is stored per invoice item (hpp/hpp_jual)

            // Get totals from request
            $totals = $request->totals ?? [];
            $subtotal = floatval($totals['subtotal'] ?? 0);
            $discountAmount = floatval($totals['discountAmount'] ?? 0);
            $taxAmount = floatval($totals['taxAmount'] ?? 0);
            // Prefer integer-rounded totals if provided by frontend to avoid rounding mismatch
            $grandTotal = isset($totals['grandTotalInt']) ? intval($totals['grandTotalInt']) : floatval($totals['grandTotal'] ?? $subtotal);
            $amountPaid = isset($totals['amountPaidInt']) ? intval($totals['amountPaidInt']) : floatval($totals['amountPaid'] ?? 0);
            // Calculate change and shortage from provided amounts (server-side authoritative)
            $paymentMethod = $totals['paymentMethod'] ?? 'cash';

            // Ensure numeric
            $amountPaidNumeric = floatval($amountPaid ?? 0);
            $grandTotalNumeric = floatval($grandTotal ?? 0);

            // If paid more than total => change (kembalian), else if paid less => shortage (kekurangan)
            $changeAmount = 0.0;
            $shortageAmount = 0.0;
            if ($amountPaidNumeric >= $grandTotalNumeric) {
                $changeAmount = $amountPaidNumeric - $grandTotalNumeric;
            } else {
                $shortageAmount = max(0, $grandTotalNumeric - $amountPaidNumeric);
            }

            // Use updateOrCreate untuk invoice
            $invoice = Invoice::updateOrCreate(
                ['visitation_id' => $request->visitation_id],
                [
                    'invoice_number' => $existingInvoice ? $existingInvoice->invoice_number : Invoice::generateInvoiceNumber(),
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'tax' => $taxAmount,
                    'discount_type' => $totals['discountType'] ?? null,
                    'discount_value' => $totals['discountValue'] ?? 0,
                    'tax_percentage' => $totals['taxPercentage'] ?? 0,
                    'total_amount' => $grandTotal,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changeAmount,
                    'shortage_amount' => $shortageAmount,
                    'payment_method' => $paymentMethod,
                    'status' => 'issued',
                    'user_id' => Auth::id(),
                    'notes' => $request->notes ?? null,
                ]);

            // Get old items for comparison if invoice exists
            $oldInvoiceItems = [];
            $previousAmountPaid = 0;
            if ($existingInvoice) {
                $previousAmountPaid = floatval($existingInvoice->amount_paid ?? 0);
                $oldInvoiceItems = $invoice->items()
                    ->get()
                    ->keyBy(function($item) {
                        return $item->billable_type . '-' . $item->billable_id;
                    });
                $invoice->items()->delete();
            }
            
            // Initialize arrays for grouping items
            $racikanGroups = [];
            $regularItems = [];
            $pharmacyFeeItems = [];
            $processedRacikanObats = [];
            // Track whether any stock reductions actually happened (useful for response)
            $stockReduced = false;

            foreach ($billingItems as $item) {
                // Identify pharmacy fee items
                if (
                    (isset($item->nama_item) && strtolower($item->nama_item) === 'jasa farmasi') ||
                    (isset($item->is_pharmacy_fee) && $item->is_pharmacy_fee)
                ) {
                    $pharmacyFeeItems[] = $item;
                    continue;
                }

                // Identify racikan items
                if (
                    $item->billable_type == 'App\Models\ERM\ResepFarmasi' && 
                    isset($item->billable->racikan_ke) && 
                    $item->billable->racikan_ke > 0
                ) {
                    $racikanKey = $item->billable->racikan_ke;
                    if (!isset($racikanGroups[$racikanKey])) {
                        $racikanGroups[$racikanKey] = [];
                    }
                    
                    // Add to group and mark as processed
                    $racikanGroups[$racikanKey][] = $item;
                    if ($item->billable && $item->billable->obat) {
                        $processedRacikanObats[] = $item->billable->obat->id;
                    }
                } else {
                    $regularItems[] = $item;
                }
            }

            // Process stock changes only when invoice becomes fully paid (amount_paid >= total_amount)
            // To avoid issues where frontend strips decimals (e.g. 256491.25 displayed/rounded),
            // compare amounts in integer rupiah (round to nearest 1) so small fractional differences
            // don't prevent stock reduction. Store raw floats too for logging.
            $amountPaidRaw = floatval($amountPaid ?? 0);
            $totalAmountRaw = floatval($grandTotal ?? 0);

            // Round up to whole rupiah (int) for comparison so we never undercount
            $previousAmountPaidInt = intval(ceil($previousAmountPaid));
            $amountPaidInt = intval(ceil($amountPaidRaw));
            $totalAmountInt = intval(ceil($totalAmountRaw));

            // Determine if payment increased compared to previous using integer rupiah
            $paymentIncreased = $existingInvoice ? ($amountPaidInt > $previousAmountPaidInt) : ($amountPaidInt > 0);

            // Only trigger reduction when the invoice was not fully paid before, payment increased,
            // and now amount_paid (rounded) is >= total_amount (rounded)
            $shouldReduceStock = ($previousAmountPaidInt < $totalAmountInt) && $paymentIncreased && ($amountPaidInt >= $totalAmountInt);

            // Add detailed logging with both raw and rounded values for debugging
            Log::info('Payment comparison (raw vs int)', [
                'previous_raw' => $previousAmountPaid,
                'amount_paid_raw' => $amountPaidRaw,
                'total_amount_raw' => $totalAmountRaw,
                'previous_int' => $previousAmountPaidInt,
                'amount_paid_int' => $amountPaidInt,
                'total_amount_int' => $totalAmountInt,
                'payment_increased' => $paymentIncreased,
                'should_reduce_stock' => $shouldReduceStock
            ]);

            if ($shouldReduceStock) {
                Log::info('Processing stock reduction - invoice reached full payment', [
                    'invoice_id' => $invoice->id,
                    'amount_paid_raw' => $amountPaidRaw,
                    'previous_amount_paid' => $previousAmountPaid,
                    'total_amount_raw' => $totalAmountRaw,
                    'is_new_invoice' => !$existingInvoice,
                    'visitation_id' => $request->visitation_id
                ]);
            } else {
                Log::info('Skipping stock reduction - invoice not fully paid or no new payment', [
                    'invoice_id' => $invoice->id,
                    'amount_paid_raw' => $amountPaidRaw,
                    'previous_amount_paid' => $previousAmountPaid,
                    'total_amount_raw' => $totalAmountRaw,
                    'is_new_invoice' => !$existingInvoice,
                    'visitation_id' => $request->visitation_id
                ]);
            }
            
            foreach ($billingItems as $item) {
                $itemKey = $item->billable_type . '-' . $item->billable_id;
                $newQty = floatval($item->qty ?? 1);

                if ($existingInvoice) {
                    // For updates, only adjust the difference
                    $oldItem = $oldInvoiceItems[$itemKey] ?? null;
                    $oldQty = $oldItem ? floatval($oldItem->quantity) : 0;

                    // If this invoice was previously unpaid (no stock reductions performed)
                    // and payment has just increased, treat old quantity as 0 so we reduce full qty
                    if ($previousAmountPaid == 0 && $paymentIncreased) {
                        $oldQty = 0;
                    }

                    $qtyDiff = $newQty - $oldQty;

                    // Skip if no quantity change (tolerant float comparison)
                    if (abs($qtyDiff) < 0.00001) {
                        continue;
                    }
                } else {
                    // For new invoices, treat the full quantity as the difference
                    $qtyDiff = $newQty;
                }
                
                // Only process stock changes if payment is made
                if ($shouldReduceStock) {
                    // For ResepFarmasi items
                    if (isset($item->billable_type) && $item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                        $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                        if ($resep && $resep->obat) {
                            // Skip if this obat was already processed as part of a racikan
                            if (in_array($resep->obat->id, $processedRacikanObats)) {
                                Log::info('Skipping already processed racikan item', [
                                    'obat_id' => $resep->obat->id,
                                    'racikan_ke' => $resep->racikan_ke,
                                    'invoice_id' => $invoice->id
                                ]);
                                continue;
                            }
                            
                            // Skip stock reduction for individual racikan items
                            if ($resep->racikan_ke > 0) {
                                continue;
                            }
                            
                            $obat = $resep->obat;
                            
                            if (abs($qtyDiff) > 0.00001) {
                                Log::info('Stock adjustment', [
                                    'invoice_id' => $invoice->id,
                                    'is_update' => (bool)$existingInvoice,
                                    'obat_id' => $obat->id,
                                    'qty_diff' => $qtyDiff,
                                    'obat_nama' => $obat->nama
                                ]);
                                
                                // If stock is being reduced (positive diff), reduce faktur stock
                                if ($qtyDiff > 0) {
                                    // Get gudang selection from frontend or use default mapping
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $item->id);
                                    
                                    // Reduce stock from selected gudang
                                    $reduced = $this->reduceGudangStock($obat->id, $qtyDiff, $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($reduced) $stockReduced = true;
                                } else if ($qtyDiff < 0) {
                                    // Get gudang selection for stock return
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $item->id);
                                    
                                    // Return stock to selected gudang
                                    $returned = $this->returnToGudangStock($obat->id, abs($qtyDiff), $gudangId, $invoice->id, $invoice->invoice_number);
                                    if ($returned) $stockReduced = true; // treat returned as stock-adjusted
                                }
                            Log::info('Stock processed via invoice (ResepFarmasi)', [
                                'obat_id' => $obat->id,
                                'obat_nama' => $obat->nama,
                                'qty_diff' => $qtyDiff,
                                'invoice_id' => $invoice->id,
                                'visitation_id' => $request->visitation_id,
                                'user_id' => Auth::id()
                            ]);
                        }
                    }
                    // For bundled Obat items (from Tindakan)
                    else if (
                        isset($item->billable_type) && 
                        $item->billable_type === 'App\\Models\\ERM\\Obat' &&
                        isset($item->keterangan) && 
                        str_contains($item->keterangan, 'Obat Bundled:')
                    ) {
                        $obat = \App\Models\ERM\Obat::find($item->billable_id);
                        if ($obat) {
                            $qty = floatval($item->qty ?? 1);
                            
                            // Get gudang selection for bundled obat
                            $gudangId = $this->getGudangForItem($request, $obat->id, 'tindakan', $item->id);
                            
                            $reduced = $this->reduceGudangStock($obat->id, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                            if ($reduced) $stockReduced = true;
                            Log::info('Stock processed via invoice (Bundled Obat)', [
                                'obat_id' => $obat->id,
                                'obat_nama' => $obat->nama,
                                'qty_reduced' => $qty,
                                'invoice_id' => $invoice->id,
                                'visitation_id' => $request->visitation_id,
                                'user_id' => Auth::id(),
                                'keterangan' => $item->keterangan
                            ]);
                        }
                    }
                }
                } // Close the main shouldReduceStock if block
            }
            
            // Process kode tindakan medications stock reduction (only when payment is made)
            if ($shouldReduceStock) {
                foreach ($kodeTindakanObats as $kodeTindakanObat) {
                    $obatId = $kodeTindakanObat['obat_id'];
                    $qty = $kodeTindakanObat['qty'];
                    
                    // Get gudang selection for kode tindakan obat
                    $gudangId = $this->getGudangForItem($request, $obatId, 'kode_tindakan', $kodeTindakanObat['billing_id'] ?? null);
                    
                    $reduced = $this->reduceGudangStock($obatId, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                    if ($reduced) $stockReduced = true;
                    
                    $obat = \App\Models\ERM\Obat::find($obatId);
                    Log::info('Stock processed via invoice (Kode Tindakan Obat)', [
                        'obat_id' => $obatId,
                        'obat_nama' => $obat ? $obat->nama : 'Unknown',
                        'qty_reduced' => $qty,
                        'invoice_id' => $invoice->id,
                        'visitation_id' => $request->visitation_id,
                        'user_id' => Auth::id(),
                        'riwayat_tindakan_id' => $kodeTindakanObat['riwayat_tindakan_id'],
                        'kode_tindakan_id' => $kodeTindakanObat['kode_tindakan_id'],
                        'payment_triggered' => true
                    ]);
                }
                // Process Lab Test medications stock reduction
                foreach ($labRequiredObats as $labMed) {
                    $obatId = $labMed['obat_id'];
                    $qty = $labMed['qty'];
                    $gudangId = $this->getGudangForItem($request, $obatId, 'lab', $labMed['billing_id'] ?? null);
                    $reduced = $this->reduceGudangStock($obatId, $qty, $gudangId, $invoice->id, $invoice->invoice_number);
                    if ($reduced) { $stockReduced = true; }
                    $obat = \App\Models\ERM\Obat::find($obatId);
                    \Illuminate\Support\Facades\Log::info('Stock processed via invoice (Lab Test Obat)', [
                        'obat_id' => $obatId,
                        'obat_nama' => $obat ? $obat->nama : 'Unknown',
                        'qty_reduced' => $qty,
                        'invoice_id' => $invoice->id,
                        'visitation_id' => $request->visitation_id,
                        'lab_test_id' => $labMed['lab_test_id'] ?? null,
                        'payment_triggered' => true
                    ]);
                }
            } else {
                Log::info('Skipped kode tindakan stock reduction - no payment made', [
                    'invoice_id' => $invoice->id,
                    'visitation_id' => $request->visitation_id,
                    'kode_tindakan_count' => count($kodeTindakanObats),
                    'amount_paid' => $amountPaid
                ]);
                \Illuminate\Support\Facades\Log::info('Skipped lab stock reduction - no payment made', [
                    'invoice_id' => $invoice->id,
                    'visitation_id' => $request->visitation_id,
                    'lab_med_count' => count($labRequiredObats),
                    'amount_paid' => $amountPaid
                ]);
            }

            // Process regular items
            foreach ($regularItems as $item) {
                $name = $item->nama_item;
                // Fix: If LabPermintaan, use LabTest name
                if ($item->billable_type == 'App\\Models\\ERM\\LabPermintaan' && !empty($item->billable_id)) {
                    $labPermintaan = \App\Models\ERM\LabPermintaan::with('labTest')->find($item->billable_id);
                    if ($labPermintaan && $labPermintaan->labTest) {
                        $name = $labPermintaan->labTest->nama ?? 'Lab Test';
                    }
                }
                if (empty($name) || $name === 'Unknown Item') {
                    if (!empty($item->billable_type) && !empty($item->billable_id)) {
                        try {
                            $billableModel = app($item->billable_type)::find($item->billable_id);
                            if ($billableModel) {
                                // Try common name fields
                                if (isset($billableModel->nama)) {
                                    $name = $billableModel->nama;
                                } elseif (isset($billableModel->name)) {
                                    $name = $billableModel->name;
                                // Special-case: if this is a RiwayatTindakan, prefer the tindakan or paket name
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\RiwayatTindakan') {
                                    // Try to load via relation fields if present
                                    try {
                                        $rt = $billableModel; // already loaded
                                        if (isset($rt->tindakan) && isset($rt->tindakan->nama)) {
                                            $name = $rt->tindakan->nama;
                                        } elseif (isset($rt->paketTindakan) && isset($rt->paketTindakan->nama)) {
                                            $name = $rt->paketTindakan->nama;
                                        }
                                    } catch (\Exception $ex) {
                                        // swallow - we'll fallback below
                                    }
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi' && isset($billableModel->obat)) {
                                    $name = $billableModel->obat->nama ?? 'Obat';
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error getting billable model name', [
                                'error' => $e->getMessage(),
                                'billable_type' => $item->billable_type,
                                'billable_id' => $item->billable_id
                            ]);
                        }
                    }
                    // If name is still empty, use a default
                    if (empty($name)) {
                        if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                            $name = 'Obat';
                        } else {
                            $name = 'Item ' . substr(md5(rand()), 0, 5);
                        }
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
                
                // Compute final amount: prefer applying active promo percent on the promo base (prefer harga_diskon)
                $unitPrice = floatval($item->jumlah ?? 0);
                $discountVal = floatval($item->diskon ?? 0);
                $discountType = $item->diskon_type ?? null;
                // initialize promo tracking variables to avoid undefined variable when promo lookup fails
                $appliedPromo = false;
                $promoBase = null;
                $promoPercent = null;

                try {
                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                    $candidates = [];
                    if (isset($item->billable_id)) $candidates[] = $item->billable_id;
                    if (isset($item->billable) && isset($item->billable->obat) && isset($item->billable->obat->id)) $candidates[] = $item->billable->obat->id;
                    if (isset($item->billable) && isset($item->billable->tindakan_id)) $candidates[] = $item->billable->tindakan_id;
                    if (isset($item->billable) && isset($item->billable->id)) $candidates[] = $item->billable->id;
                    $candidates = array_values(array_filter(array_unique($candidates)));

                    if (!empty($candidates)) {
                        $promoItems = \App\Models\Marketing\PromoItem::whereIn('item_id', $candidates)
                            ->whereIn('item_type', ['tindakan','obat'])
                            ->whereHas('promo', function($q) use ($today){
                                $q->where(function($q2) use ($today){
                                    $q2->whereNotNull('start_date')->whereNotNull('end_date')
                                        ->where('start_date','<=',$today)
                                        ->where('end_date','>=',$today);
                                })->orWhere(function($q2) use ($today){
                                    $q2->whereNotNull('start_date')->whereNull('end_date')
                                        ->where('start_date','<=',$today);
                                })->orWhere(function($q2) use ($today){
                                    $q2->whereNull('start_date')->whereNotNull('end_date')
                                        ->where('end_date','>=',$today);
                                });
                            })->get();

                        if (!$promoItems->isEmpty()) {
                            $max = $promoItems->max('discount_percent');
                            if ($max > 0) {
                                // apply percent discount from promo on promo_price_base (prefer harga_diskon)
                                $winning = $promoItems->firstWhere('discount_percent', $max);
                                $basePrice = null;
                                if ($winning) {
                                    if ($winning->item_type === 'tindakan') {
                                        $t = \App\Models\ERM\Tindakan::find($winning->item_id);
                                        $basePrice = $t->harga_diskon ?? $t->harga ?? null;
                                    } elseif ($winning->item_type === 'obat') {
                                        $o = \App\Models\ERM\Obat::withInactive()->find($winning->item_id);
                                        $basePrice = $o->harga_diskon ?? $o->harga_net ?? null;
                                    }
                                }
                                if (!$basePrice && isset($item->billable)) {
                                    $basePrice = $item->billable->harga_diskon ?? $item->billable->unit_price ?? null;
                                }
                                if (!$basePrice) $basePrice = $unitPrice;

                                // Overwrite discount to percent and compute unit price after discount based on promo base
                                $discountVal = $max;
                                $discountType = '%';
                                $unitPriceAfter = max(0, $basePrice - ($basePrice * ($discountVal / 100)));
                                $finalAmountComputed = $unitPriceAfter * floatval($item->qty ?? 1);
                                // mark applied promo details for later nominal storage calculation
                                $appliedPromo = true;
                                $promoBase = $basePrice;
                                $promoPercent = $max;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // fallback to existing billing discount fields if promo detection fails
                    Log::warning('Failed to evaluate promo for invoice item: '.$e->getMessage());
                }

                // If $finalAmountComputed not set by promo flow, compute using existing billing discount fields
                if (!isset($finalAmountComputed)) {
                    if ($discountVal > 0) {
                        if ($discountType === '%') {
                            $unitPriceAfter = $unitPrice - ($unitPrice * ($discountVal / 100));
                        } else {
                            $unitPriceAfter = $unitPrice - $discountVal;
                        }
                        $unitPriceAfter = max(0, $unitPriceAfter);
                    } else {
                        $unitPriceAfter = $unitPrice;
                    }
                    $finalAmountComputed = $unitPriceAfter * floatval($item->qty ?? 1);
                }

                    // Compute stored nominal discount value
                    // Special-case: bundled obat items should not carry an additional charged final amount
                    if (isset($item->keterangan) && str_contains($item->keterangan, 'Obat Bundled:')) {
                        $storedDiscount = 0;
                        $storedDiscountType = null;
                        $finalAmountComputed = 0;
                        $unitPrice = 0;
                    } else {
                        $storedDiscount = 0;
                        $storedDiscountType = null;
                    }

                    if ($appliedPromo && $promoBase !== null && $promoPercent !== null) {
                        // Nominal gap between original unit price and promo base
                        $gap = max(0, $unitPrice - $promoBase);
                        $percentNominal = ($promoBase * ($promoPercent / 100));
                        $storedDiscount = round($gap + $percentNominal, 2);
                        $storedDiscountType = 'nominal';
                    } else {
                        // Legacy behavior: convert percent to nominal for storage
                        if ($discountType === '%') {
                            $storedDiscount = round($unitPrice * ($discountVal / 100), 2);
                            $storedDiscountType = 'nominal';
                        } else {
                            $storedDiscount = round(floatval($discountVal ?? 0), 2);
                            $storedDiscountType = 'nominal';
                        }
                    }

                    InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $name,
                    'description' => $description,
                    'quantity' => floatval($item->qty ?? 1),
                    // store original unit price (before discount) where available
                    'unit_price' => $unitPrice,
                    'hpp' => (function() use ($item) {
                        try {
                            if (!empty($item->billable_type) && !empty($item->billable_id)) {
                                if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                                    if ($resep && isset($resep->obat) && $resep->obat) return $resep->obat->hpp ?? null;
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\Obat') {
                                    $obat = \App\Models\ERM\Obat::withInactive()->find($item->billable_id);
                                    if ($obat) return $obat->hpp ?? null;
                                }
                                // fallback: try to resolve model and common field
                                $model = app($item->billable_type)::find($item->billable_id);
                                if ($model) {
                                    if (isset($model->hpp)) return $model->hpp;
                                    if (isset($model->obat) && isset($model->obat->hpp)) return $model->obat->hpp;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get hpp for invoice item: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'hpp_jual' => (function() use ($item) {
                        try {
                            if (!empty($item->billable_type) && !empty($item->billable_id)) {
                                if ($item->billable_type == 'App\\Models\\ERM\\ResepFarmasi') {
                                    $resep = \App\Models\ERM\ResepFarmasi::find($item->billable_id);
                                    if ($resep && isset($resep->obat) && $resep->obat) return $resep->obat->hpp_jual ?? null;
                                } elseif ($item->billable_type == 'App\\Models\\ERM\\Obat') {
                                    $obat = \App\Models\ERM\Obat::withInactive()->find($item->billable_id);
                                    if ($obat) return $obat->hpp_jual ?? null;
                                }
                                $model = app($item->billable_type)::find($item->billable_id);
                                if ($model) {
                                    if (isset($model->hpp_jual)) return $model->hpp_jual;
                                    if (isset($model->obat) && isset($model->obat->hpp_jual)) return $model->obat->hpp_jual;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get hpp_jual for invoice item: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'discount' => $storedDiscount,
                    'discount_type' => $storedDiscountType,
                    // final_amount must reflect discount application
                    'final_amount' => $finalAmountComputed,
                    'billable_type' => $item->billable_type ?? null,
                    'billable_id' => $item->billable_id ?? null,
                ]);
            }
            
            // Preload active PaketRacikan with details (for racikan name matching)
            $activePaketRacikans = PaketRacikan::with(['details' => function($q){ $q->select('id','paket_racikan_id','obat_id','dosis'); }])
                ->where('is_active', true)
                ->get(['id','nama_paket','is_active']);

            // Process racikan groups
            foreach ($racikanGroups as $racikanKey => $racikanItems) {
                // Use the first item as base
                $firstItem = $racikanItems[0];
                
                // Get the total price for this racikan group
                $totalPrice = 0;
                $obatList = [];
                $qty = 0;
                
                foreach ($racikanItems as $item) {
                    $totalPrice += floatval($item->jumlah ?? 0);
                    
                    // Get the obat name for description
                    if (isset($item->billable) && $item->billable->obat) {
                        $obatList[] = $item->billable->obat->nama;
                    }
                    
                    // Use the qty/bungkus from the first item (should be the same for all items in racikan)
                        if ($qty == 0) {
                        $qty = floatval($item->billable->bungkus ?? $item->qty ?? 30); // Default to 30 if not specified
                    }
                }
                
                    // Format the description as a list
                    $formattedObatList = array_map(function($obat) {
                        return "- " . $obat;
                    }, $obatList);
                    
                    $description = implode("\n", $formattedObatList);

                    // Determine racikan display name: match to PaketRacikan (obat_id + dosis order-insensitive)
                    $racikanDisplayName = 'Obat Racikan';
                    try {
                        $normalizeDose = function($val) {
                            if ($val === null) return '';
                            $s = trim(strtolower((string)$val));
                            $s = str_replace([','], ['.'], $s);
                            if (preg_match('/\d+(?:\.\d+)?/', $s, $m)) {
                                return rtrim(rtrim($m[0], '0'), '.') ?: $m[0];
                            }
                            return $s;
                        };
                        $compMap = [];
                        foreach ($racikanItems as $ri) {
                            $billable = $ri->billable ?? null;
                            $ob = ($billable && isset($billable->obat)) ? $billable->obat : null;
                            $dose = $billable ? ($billable->dosis ?? null) : null;
                            if ($ob && isset($ob->id)) {
                                $key = $ob->id . '|' . $normalizeDose($dose);
                                $compMap[$key] = true;
                            }
                        }
                        foreach ($activePaketRacikans as $paket) {
                            $details = $paket->details;
                            if (!$details || $details->count() === 0) continue;
                            if ($details->count() !== count($compMap)) continue;
                            $allMatch = true;
                            foreach ($details as $d) {
                                $dKey = ($d->obat_id ?? '0') . '|' . $normalizeDose($d->dosis ?? '');
                                if (!isset($compMap[$dKey])) { $allMatch = false; break; }
                            }
                            if ($allMatch) { $racikanDisplayName = $paket->nama_paket; break; }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Racikan paket matching (invoice) failed: ' . $e->getMessage());
                    }
                    
                    // Handle stock adjustments for racikan items only when invoice payment triggers reduction
                    // Compute $qtyDiff for this racikan group. For new invoices or when previous payment was 0,
                    // treat the full qty as the diff. For updates where invoice was already paid, we avoid
                    // changing stock here (existing invoice adjustments are handled elsewhere).
                    $newRacikanQty = floatval($qty ?? 0);
                    $racikanQtyDiff = 0;
                    if (!$existingInvoice) {
                        $racikanQtyDiff = $newRacikanQty;
                    } else {
                        // If previous invoice had no payment (previousAmountPaid == 0) and now payment increased,
                        // we should reduce full qty. Otherwise, conservatively set diff to 0 to avoid double-processing.
                        if (floatval($previousAmountPaid) == 0 && $paymentIncreased) {
                            $racikanQtyDiff = $newRacikanQty;
                        } else {
                            $racikanQtyDiff = 0;
                        }
                    }

                    // Only perform stock operations if the invoice reached full payment (shouldReduceStock)
                    // and there is a non-zero qty difference to apply.
                    if (!empty($shouldReduceStock) && $racikanQtyDiff != 0) {
                        foreach ($racikanItems as $racikanItem) {
                            if (isset($racikanItem->billable) && $racikanItem->billable->obat) {
                                $obat = $racikanItem->billable->obat;

                                // For racikan components, prefer using the stored 'jumlah' value
                                // (which we persist as 'stok_dikurangi' during resep submit). Fall
                                // back to the racikan group qty diff if not present.
                                $componentQty = floatval($racikanItem->billable->jumlah ?? 0);
                                if ($componentQty <= 0) {
                                    // fallback to group diff
                                    $componentQty = floatval($racikanQtyDiff);
                                }

                                Log::info('Processing racikan stock adjustment', [
                                    'obat_id' => $obat->id,
                                    'component_qty' => $componentQty,
                                    'group_qty_diff' => $racikanQtyDiff,
                                    'is_update' => (bool)$existingInvoice,
                                    'invoice_id' => $invoice->id
                                ]);

                                if ($componentQty > 0) {
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $racikanItem->id);
                                    $this->reduceGudangStock($obat->id, $componentQty, $gudangId, $invoice->id, $invoice->invoice_number);
                                } else if ($componentQty < 0) {
                                    $gudangId = $this->getGudangForItem($request, $obat->id, 'resep', $racikanItem->id);
                                    $this->returnToGudangStock($obat->id, abs($componentQty), $gudangId, $invoice->id, $invoice->invoice_number);
                                }

                                Log::info('Racikan stock processed', [
                                    'invoice_id' => $invoice->id,
                                    'is_update' => (bool)$existingInvoice,
                                    'racikan_ke' => $racikanKey,
                                    'obat_id' => $obat->id,
                                    'component_qty' => $componentQty
                                ]);
                            }
                        }
                    }
                    
                    // For racikan, the unit price is the total price of all components per unit (bungkus)
                    $unitPrice = (float) $totalPrice;  // Total price of all components is treated as unit price
                    // Use the computed $qty (bungkus) for final amount. Previously code used $newQty which
                    // could be from an earlier loop iteration and lead to final_amount equaling unit_price.
                    $finalAmount = $unitPrice * (int) $qty; // Final amount = unit price × qty (bungkus)
                    // Create single invoice item for the racikan group
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $racikanDisplayName,
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'hpp' => (function() use ($racikanItems) {
                        try {
                            $hppVals = [];
                            foreach ($racikanItems as $ri) {
                                if (isset($ri->billable) && isset($ri->billable->obat) && $ri->billable->obat) {
                                    $hppVals[] = $ri->billable->obat->hpp ?? null;
                                }
                            }
                            $hppVals = array_filter($hppVals, function($v){ return !is_null($v); });
                            if (!empty($hppVals)) return round(array_sum($hppVals)/count($hppVals), 2);
                        } catch (\Exception $e) {
                            Log::warning('Failed to compute racikan hpp: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'hpp_jual' => (function() use ($racikanItems) {
                        try {
                            $vals = [];
                            foreach ($racikanItems as $ri) {
                                if (isset($ri->billable) && isset($ri->billable->obat) && $ri->billable->obat) {
                                    $vals[] = $ri->billable->obat->hpp_jual ?? null;
                                }
                            }
                            $vals = array_filter($vals, function($v){ return !is_null($v); });
                            if (!empty($vals)) return round(array_sum($vals)/count($vals), 2);
                        } catch (\Exception $e) {
                            Log::warning('Failed to compute racikan hpp_jual: ' . $e->getMessage());
                        }
                        return null;
                    })(),
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => $finalAmount,
                    'billable_type' => 'App\\Models\\ERM\\ResepFarmasi',
                    'billable_id' => null, // No specific billable_id since it's a group
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
                    'hpp' => null,
                    'hpp_jual' => null,
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
                    'hpp' => null,
                    'hpp_jual' => null,
                    'discount' => 0,
                    'discount_type' => null,
                    'final_amount' => floatval($totals['shippingFee']),
                    'billable_type' => null,
                    'billable_id' => null,
                ]);
            }

            // If payment method is 'piutang', create a piutang record for the outstanding amount
            try {
                if (isset($paymentMethod) && $paymentMethod === 'piutang') {
                    // Option B: always record full invoice amount as piutang (receivable)
                    $piutangAmount = floatval($grandTotalNumeric);
                    // (status calculation removed — handled elsewhere if needed)

                    Piutang::create([
                        'visitation_id' => $request->visitation_id,
                        'invoice_id' => $invoice->id,
                        // store full invoice total as requested
                        'amount' => $piutangAmount,
                        // always start as unpaid per request
                        'payment_status' => 'unpaid',
                        // do not set payment_date when creating initial receivable
                        'payment_date' => null,
                        // leave payment_method empty and user_id null as requested
                        'payment_method' => null,
                        'notes' => $request->notes ?? null,
                        'user_id' => null
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to create piutang record: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil dibuat',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'stock_reduced' => $stockReduced,
                'stock_message' => $stockReduced ? 'Stok berhasil dikurangi sesuai pembayaran.' : 'Stok tidak dikurangi (invoice belum lunas atau tidak ada item stok).'
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

    /**
     * Restore a soft-deleted billing item
     */
    public function restore($id)
    {
        $item = Billing::withTrashed()->findOrFail($id);
        if ($item->trashed()) {
            $item->restore();
            return response()->json(['message' => 'Item billing berhasil dikembalikan']);
        }
        return response()->json(['message' => 'Item tidak berada di trash'], 400);
    }

    /**
     * Permanently delete a billing item
     */
    public function forceDelete($id)
    {
        $item = Billing::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Item billing dihapus permanen']);
    }

    /**
     * Soft-delete all billing items for a visitation (from index)
     */
    public function trashByVisitation($visitation_id)
    {
        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::where('visitation_id', $visitation_id)->delete();
        return response()->json(['message' => 'Billing untuk kunjungan dipindahkan ke trash', 'count' => $count]);
    }

    /**
     * Restore all billing items for a visitation
     */
    public function restoreByVisitation($visitation_id)
    {
        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::withTrashed()->where('visitation_id', $visitation_id)->restore();
        return response()->json(['message' => 'Billing untuk kunjungan dikembalikan', 'count' => $count]);
    }

    /**
     * Force delete all billing items for a visitation
     */
    public function forceDeleteByVisitation($visitation_id)
    {
        $visitation = \App\Models\ERM\Visitation::findOrFail($visitation_id);
        $count = Billing::withTrashed()->where('visitation_id', $visitation_id)->forceDelete();
        return response()->json(['message' => 'Billing untuk kunjungan dihapus permanen', 'count' => $count]);
    }

    public function getVisitationsData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $dokterId = $request->input('dokter_id');
        $klinikId = $request->input('klinik_id');
        $includeDeleted = filter_var($request->input('include_deleted', false), FILTER_VALIDATE_BOOLEAN);
        
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
            // Show visitations that either have no invoice OR have an unpaid invoice,
            // AND ensure they have at least one billing item (respect includeDeleted)
            $visitations->where(function($query) use ($includeDeleted) {
                $query->where(function($q) {
                          $q->whereDoesntHave('invoice')
                            ->orWhereHas('invoice', function($iq) {
                                  $iq->where('amount_paid', 0);
                            });
                      })
                      ->whereExists(function($sub) use ($includeDeleted) {
                          $sub->select(DB::raw(1))
                              ->from('finance_billing')
                              ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                          if (!$includeDeleted) {
                              $sub->whereNull('finance_billing.deleted_at');
                          }
                      });
            });
        } elseif ($statusFilter === 'belum_lunas') {
            // Partially paid invoices
            $visitations->whereHas('invoice', function($q) {
                $q->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'total_amount');
            });
        } elseif ($statusFilter === 'sudah') {
            // Only visitations with a paid invoice
            $visitations->whereHas('invoice', function($q) {
                $q->where('amount_paid', '>', 0);
            });
        } elseif ($statusFilter === 'piutang') {
            // Visitations where invoice.payment_method is piutang
            $visitations->whereHas('invoice', function($q) {
                $q->where('payment_method', 'piutang');
            });
        } else {
            // Semua status: by default exclude visitations that only have trashed billings
            if (!$includeDeleted) {
                $visitations->where(function($q) {
                    $q->whereHas('invoice')
                      ->orWhereExists(function($sub) {
                          $sub->select(DB::raw(1))
                              ->from('finance_billing')
                              ->whereNull('finance_billing.deleted_at')
                              ->whereColumn('finance_billing.visitation_id', 'erm_visitations.id');
                      });
                });
            }
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
                // Show dokter name combined with specialization if available, e.g. "dr Bambang (Penyakit Dalam)"
                if ($visitation->dokter && $visitation->dokter->user) {
                    $name = $visitation->dokter->user->name;
                    if ($visitation->dokter->spesialisasi && $visitation->dokter->spesialisasi->nama) {
                        return $name . ' (' . $visitation->dokter->spesialisasi->nama . ')';
                    }
                    return $name;
                }
                return '-';
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
            ->addColumn('invoice_number', function ($visitation) {
                // Return associated invoice number if exists, otherwise dash
                if ($visitation->invoice && isset($visitation->invoice->invoice_number)) {
                    return $visitation->invoice->invoice_number;
                }
                return '-';
            })
            ->addColumn('payment_method', function ($visitation) {
                if ($visitation->invoice && isset($visitation->invoice->payment_method)) {
                    return $visitation->invoice->payment_method;
                }
                return null;
            })
                ->addColumn('status', function ($visitation) {
                        // If invoice exists, determine full/partial/unpaid status
                        if ($visitation->invoice) {
                            $amountPaid = floatval($visitation->invoice->amount_paid ?? 0);
                            $totalAmount = floatval($visitation->invoice->total_amount ?? 0);

                            // Fully paid
                            if ($totalAmount > 0 && $amountPaid >= $totalAmount) {
                                return '<span style="color: #fff; background: #28a745; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Sudah Bayar</span>';
                            }

                            // Partially paid
                            if ($amountPaid > 0 && $amountPaid < $totalAmount) {
                                return '<span style="color: #fff; background: #ffc107; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Lunas</span>';
                            }

                            // amount_paid == 0 falls through to check billings below
                        }

                        // Check if all billings for this visitation are trashed (if there are any)
                        $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id)->count();
                        $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id)->count();
                        if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
                            return '<span style="color: #fff; background: #6c757d; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Terhapus</span>';
                        }

                        // No invoice or unpaid invoice
                        return '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Dibayar</span>';
                })
            ->addColumn('action', function ($visitation) {
                $action = '<a href="'.route('finance.billing.create', $visitation->id).'" class="btn btn-sm btn-primary">Lihat Billing</a>';

                // Add "Cetak Nota" buttons if invoice exists
                if ($visitation->invoice) {
                    $action .= ' <a href="'.route('finance.invoice.print-nota', $visitation->invoice->id).'" class="btn btn-sm btn-success ml-1" target="_blank">Cetak Nota</a>';
                    $action .= ' <a href="'.route('finance.invoice.print-nota-v2', $visitation->invoice->id).'" class="btn btn-sm btn-warning ml-1" target="_blank">Cetak Nota v2</a>';
                }

                // Actions for soft-delete/restore/force per visitation
                $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id)->count();
                $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id)->count();

                if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
                    // all billings trashed -> show restore and force-delete (force as text 'Permanent Delete')
                    $action .= ' <button data-id="'.$visitation->id.'" class="btn btn-sm btn-info btn-restore-visitation ml-1">Restore</button>';
                    // add data-no-icon to prevent client-side icon mapping
                    $action .= ' <button data-id="'.$visitation->id.'" data-no-icon="1" class="btn btn-sm btn-danger btn-force-visitation ml-1">Permanent Delete</button>';
                } elseif ($totalBillings > 0) {
                    // has non-deleted billing -> show trash as icon-only button
                    $action .= ' <button data-id="'.$visitation->id.'" class="btn btn-sm btn-danger btn-trash-visitation ml-1" title="Hapus"><i class="ti-trash" aria-hidden="true"></i></button>';
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
     * Get gudang mappings and available gudangs for billing
     */
    public function getGudangData()
    {
        $gudangs = Gudang::orderBy('nama')->get();
        $gudangMappings = [
            'resep' => GudangMapping::getDefaultGudangId('resep'),
            'tindakan' => GudangMapping::getDefaultGudangId('tindakan'),
            'kode_tindakan' => GudangMapping::getDefaultGudangId('kode_tindakan'),
            'lab' => GudangMapping::getDefaultGudangId('lab'),
        ];

        return response()->json([
            'gudangs' => $gudangs,
            'mappings' => $gudangMappings,
        ]);
    }

    /**
     * Reduce stock using StokService with FIFO logic
     */
    private function reduceGudangStock($obatId, $qty, $gudangId = null, $invoiceId = null, $invoiceNumber = null)
    {
        // Default gudang ID jika tidak dispesifikasikan
        if (!$gudangId) {
            $defaultGudang = \App\Models\ERM\Gudang::first();
            $gudangId = $defaultGudang ? $defaultGudang->id : null;
        }

        if (!$gudangId) {
            Log::error("Tidak ada gudang yang tersedia untuk pengurangan stok obat ID: " . $obatId);
            return false;
        }

        // Gunakan StokService untuk pengurangan stok dengan FIFO logic
        $stokService = new StokService();

        try {
            // Ambil stok dari gudang berdasarkan FIFO (tanggal expired terlama)
            $stokList = \App\Models\ERM\ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('stok', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->get();

            $remainingQty = $qty;

            foreach ($stokList as $stok) {
                if ($remainingQty <= 0) break;

                $qtyToReduce = min($remainingQty, $stok->stok);
                
                // Debug: log the computed qty and types just before calling StokService
                try {
                    Log::info('About to call StokService::kurangiStok', [
                        'obat_id' => $obatId,
                        'gudang_id' => $gudangId,
                        'batch' => $stok->batch,
                        'qtyToReduce' => $qtyToReduce,
                        'qtyToReduce_type' => gettype($qtyToReduce),
                        'stok_value' => $stok->stok,
                        'stok_value_type' => gettype($stok->stok),
                    ]);
                } catch (\Exception $e) {
                    // swallow logging errors
                }

                // Kurangi stok menggunakan StokService dengan referensi invoice
                $stokService->kurangiStok(
                    $obatId, 
                    $gudangId, 
                    $qtyToReduce, 
                    $stok->batch,
                    'invoice_penjualan',
                    $invoiceId,
                    $invoiceNumber ? "Penjualan via Invoice: {$invoiceNumber}" : "Penjualan obat"
                );
                
                Log::info("Stok berkurang dari gudang", [
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'batch' => $stok->batch,
                    'qty' => $qtyToReduce,
                    'before_stock' => $stok->stok,
                    'expiration_date' => $stok->expiration_date
                ]);

                $remainingQty -= $qtyToReduce;
            }

            if ($remainingQty > 0) {
                Log::error("Stok tidak cukup untuk obat ID: " . $obatId . " di gudang ID: " . $gudangId . ". Kurang: " . $remainingQty);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Gagal mengurangi stok obat ID: " . $obatId . " dari gudang ID: " . $gudangId . ". Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy method kept for backwards compatibility
     * Now delegates to reduceGudangStock
     */
    private function reduceFakturStock($obatId, $qty)
    {
        return $this->reduceGudangStock($obatId, $qty);
    }

    /**
     * Get gudang ID for specific item based on frontend selection or mapping
     */
    private function getGudangForItem($request, $obatId, $transactionType, $billingId = null)
    {
        // Check if frontend sent specific gudang selection for this item
        $gudangSelections = $request->input('gudang_selections', []);

        // 1) Prefer direct billing-id keyed selection (frontend uses billing row id as key)
        if ($billingId !== null && isset($gudangSelections[$billingId]) && $gudangSelections[$billingId]) {
            return $gudangSelections[$billingId];
        }

        // 2) Then check typed key e.g. resep_{obatId}, tindakan_{obatId}, kode_tindakan_{obatId}
        $itemKey = $transactionType . '_' . $obatId;
        if (isset($gudangSelections[$itemKey]) && $gudangSelections[$itemKey]) {
            return $gudangSelections[$itemKey];
        }

        // 3) For kode_tindakan try to resolve by spesialisasi (if billing row references RiwayatTindakan)
        if ($transactionType === 'kode_tindakan') {
            try {
                if ($billingId) {
                    $billingRow = \App\Models\Finance\Billing::find($billingId);
                    if ($billingRow && isset($billingRow->billable_type) && $billingRow->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                        $riwayat = \App\Models\ERM\RiwayatTindakan::with('tindakan')->find($billingRow->billable_id);
                        if ($riwayat && $riwayat->tindakan && isset($riwayat->tindakan->spesialis_id)) {
                            $spesialisId = $riwayat->tindakan->spesialis_id;
                            $mapping = GudangMapping::resolveGudangForTransaction($transactionType, 'spesialisasi', $spesialisId);
                            if ($mapping && $mapping->gudang_id) {
                                return $mapping->gudang_id;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore and fallback to default mapping
                \Illuminate\Support\Facades\Log::warning('Failed to resolve gudang by spesialisasi: ' . $e->getMessage());
            }
        }

        // 4) Fallback to mapping default (transaction-only)
        $defaultGudangId = GudangMapping::resolveGudangForTransaction($transactionType);
        if ($defaultGudangId) {
            return $defaultGudangId->gudang_id ?? ($defaultGudangId);
        }

        // 4) Last resort: use first available gudang
        $defaultGudang = \App\Models\ERM\Gudang::first();
        return $defaultGudang ? $defaultGudang->id : null;
    }

    /**
     * Return stock to gudang using StokService
     */
    private function returnToGudangStock($obatId, $qty, $gudangId = null, $invoiceId = null, $invoiceNumber = null)
    {
        if (!$gudangId) {
            $defaultGudang = Gudang::first();
            $gudangId = $defaultGudang ? $defaultGudang->id : null;
        }

        if (!$gudangId) {
            Log::error("Tidak ada gudang yang tersedia untuk pengembalian stok obat ID: " . $obatId);
            return false;
        }

        // Gunakan StokService untuk penambahan stok
        $stokService = new StokService();

        try {
            // Get the most recent batch for return reference
            $lastBatch = \App\Models\ERM\ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->orderBy('created_at', 'desc')
                ->first();

            $batchName = $lastBatch ? $lastBatch->batch : 'RETURN-' . date('YmdHis');
            $expDate = $lastBatch ? $lastBatch->expiration_date : now()->addYears(1);

            // Tambah stok menggunakan StokService dengan referensi invoice
            $keterangan = $invoiceNumber ? "Pengembalian stok dari Invoice: {$invoiceNumber}" : "Pengembalian stok";
            $stokService->tambahStok(
                $obatId, 
                $gudangId, 
                $qty, 
                $batchName, 
                $expDate,
                null, // rak
                null, // lokasi
                null, // hargaBeli
                null, // hargaBeliJual
                'invoice_return', // refType
                $invoiceId, // refId
                $keterangan // keterangan
            );

            Log::info("Stok dikembalikan ke gudang menggunakan StokService", [
                'obat_id' => $obatId,
                'gudang_id' => $gudangId,
                'batch' => $batchName,
                'qty_returned' => $qty
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Gagal mengembalikan stok obat ID: " . $obatId . " ke gudang ID: " . $gudangId . ". Error: " . $e->getMessage());
            return false;
        }
    }
}
